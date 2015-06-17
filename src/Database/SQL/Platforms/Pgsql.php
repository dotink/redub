<?php namespace Redub\Database\SQL
{
	use Dotink\Flourish;

	/**
	 *
	 */
	class Pgsql extends AbstractPlatform
	{
		const DEFAULT_SCHEMA  = 'public';
		const PLATFORM_NAME   = 'Pgsql';
		const REGEX_DATA_TYPE = '#([\w ]+)\s*(?:\(\s*(\d+)(?:\s*,\s*(\d+))?\s*\))?#';

		static protected $outgoingDataTypes = [
			'boolean'   => 'boolean',
			'integer'   => 'int',
			'float'     => 'decimal',
			'timestamp' => 'timestamp',
			'date'      => 'date',
			'time'      => 'time',
			'string'    => 'varchar',
			'text'      => 'text',
			'binary'    => 'bytea'
		];

		static protected $incomingDataTypes = [
			'boolean'			=> 'boolean',
			'smallint'			=> 'integer',
			'int'				=> 'integer',
			'bigint'			=> 'integer',
			'serial'			=> 'integer',
			'bigserial'			=> 'integer',
			'decimal'           => 'float',
			'real'				=> 'float',
			'double'			=> 'float',
			'numeric'			=> 'float',
			'text'				=> 'text',
			'mediumtext'		=> 'text',
			'longtext'			=> 'text',
			'point'             => 'string',
			'line'              => 'string',
			'lseg'              => 'string',
			'box'               => 'string',
			'path'              => 'string',
			'polygon'           => 'string',
			'circle'            => 'string',
			'uuid'              => 'string',
			'character varying'	=> 'string',
			'character'			=> 'character',
			'timestamp'			=> 'timestamp',
			'date'				=> 'date',
			'time'				=> 'time',
			'bytea'				=> 'binary'
		];

		static protected $maxMinValues = [
			'smallint'  => ['min' => -32768,               'max' => 32767],
			'int'       => ['min' => -2147483648,          'max' => 2147483647],
			'bigint'    => ['min' => -9223372036854775808, 'max' => 9223372036854775807],
			'serial'    => ['min' => -2147483648,          'max' => 2147483647],
			'bigserial' => ['min' => -9223372036854775808, 'max' => 9223372036854775807]
		];


		protected $columns = array();

		protected $keys = array();

		protected $routes = array();

		protected $normalizedTables = array();




		/**
		 *
		 */
		public function resolveFields($connection, $table)
		{
			$this->resolveColumns($connection);

			$table = $this->normalizeTable($table);
			$alias = $connection->getAlias();

			if (!isset($this->columns[$alias][$table])) {
				throw new Flourish\ProgrammerException(
					'Could not resolve fields for table "%s" on connection "%s"',
					$table,
					$alias
				);
			}

			return array_keys($this->columns[$alias][$table]);
		}


		/**
		 *
		 */
		public function resolveFieldInfo($connection, $table, $column, $type)
		{
			$this->resolveColumns($connection);

			$table = $this->normalizeTable($table);
			$alias = $connection->getAlias();

			if (!in_array($type, ['type', 'default', 'auto', 'nullable', 'comment'])) {
				throw new Flourish\ProgrammerException(
					'Unknown field information type "%s"',
					$type
				);
			}

			if (!isset($type, $this->columns[$alias][$table][$column])) {
				throw new Flourish\ProgrammerException(
					'Could not resolve column "%s" information for table "%s" on connection "%s"',
					$column,
					$table,
					$alias
				);
			}

			return $this->columns[$alias][$table][$column][$type];
		}


		/**
		 *
		 */
		public function resolveIdentity($connection, $table)
		{
			$this->resolveKeysAndIndexes($connection);

			$table = $this->normalizeTable($table);
			$alias = $connection->getAlias();

			if (!isset($this->keys[$alias][$table]['primary'])) {
				throw new Flourish\ProgrammerException(
					'Could not resolve identity for table "%s" on connection "%s"',
					$table,
					$alias
				);
			}

			return $this->keys[$alias][$table]['primary'];
		}


		/**
		 * Resolves a list of repositories for the connection
		 */
		public function resolveRepositories($connection)
		{
			$alias = $connection->getAlias();

			if (isset($this->tables[$alias])) {
				return $this->tables[$alias];
			}

			$tables = array();
			$result = $connection->execute(
				"SELECT
					schemaname as \"schema\",
					tablename  as \"table\"
				FROM
					pg_tables
				WHERE
					tablename !~ '^(pg|sql)_'
				ORDER BY
					LOWER(tablename)
				"
			);

			foreach ($result as $row) {
				if ($row['schema'] == static::DEFAULT_SCHEMA) {
					$tables[] = strtolower($row['table']);
				} else {
					$tables[] = strtolower($row['schema'] . '.' . $row['table']);
				}
			}

			return $this->tables[$alias] = $tables;
		}


		/**
		 *
		 */
		public function resolveUniqueIndexes($connection, $table)
		{
			$this->resolveKeysAndIndexes($connection);

			$table = $this->normalizeTable($table);
			$alias = $connection->getAlias();

			if (!isset($this->keys[$alias][$table]['unique'])) {
				throw new Flourish\ProgrammerException(
					'Could not resolve unique indexes for table "%s" on connection "%s"',
					$table,
					$alias
				);
			}

			return $this->keys[$alias][$table]['unique'];
		}


		/**
		 *
		 */
		public function resolveRoutesToMany($connection, $table, $unique)
		{
			$this->resolveRoutes($connection);

			$table = $this->normalizeTable($table);
			$alias = $connection->getAlias();

			return $unique
				? $this->routes[$alias][$table]['hasManyUnique']
				: $this->routes[$alias][$table]['hasMany'];
		}


		/**
		 *
		 */
		public function resolveRoutesToOne($connection, $table, $unique)
		{
			$this->resolveRoutes($connection);

			$table = $this->normalizeTable($table);
			$alias = $connection->getAlias();

			return $unique
				? $this->routes[$alias][$table]['hasOneUnique']
				: $this->routes[$alias][$table]['hasOne'];
		}


		/**
		 *
		 */
		protected function normalizeTable($table)
		{
			if (isset($this->normalizedTables[$table])) {
				return $this->normalizedTables[$table];
			}

			if (strpos($table, '.') !== FALSE) {
				list($schema, $schemaless_table) = explode('.', $table);

				if ($schema == static::DEFAULT_SCHEMA) {
					$this->normalizedTables[$table] = $schemaless_table;
				} else {
					$this->normalizedTables[$table] = $table;
				}

			} else {
				$this->normalizedTables[$table] = $table;
			}

			return $this->normalizedTables[$table];
		}



		/**
		 *
		 */
		protected function resolveKeysAndIndexes($connection)
		{
			$alias = $connection->getAlias();

			if (isset($this->keys[$alias])) {
				return;
			}

			$tables  = $this->resolveRepositories($connection);
			$keys    = array();

			foreach ($tables as $table) {
				$keys[$table] = [
					'primary' => array(),
					'unique'  => array(),
					'foreign' => array()
				];
			}

			$result = $connection->execute(
				"(SELECT
					LOWER(s.nspname) AS \"schema\",
					LOWER(t.relname) AS \"table\",
					con.conname AS constraint_name,
					CASE con.contype
						WHEN 'f' THEN 'foreign'
						WHEN 'p' THEN 'primary'
						WHEN 'u' THEN 'unique'
					END AS type,
					LOWER(col.attname) AS column,
					LOWER(fs.nspname) AS foreign_schema,
					LOWER(ft.relname) AS foreign_table,
					LOWER(fc.attname) AS foreign_column,
					CASE con.confdeltype
						WHEN 'c' THEN 'cascade'
						WHEN 'a' THEN 'no_action'
						WHEN 'r' THEN 'restrict'
						WHEN 'n' THEN 'set_null'
						WHEN 'd' THEN 'set_default'
					END AS on_delete,
					CASE con.confupdtype
						WHEN 'c' THEN 'cascade'
						WHEN 'a' THEN 'no_action'
						WHEN 'r' THEN 'restrict'
						WHEN 'n' THEN 'set_null'
						WHEN 'd' THEN 'set_default'
					END AS on_update,
					CASE
						WHEN con.conkey IS NOT NULL THEN position(
							'-'||col.attnum||'-' in '-'||array_to_string(con.conkey, '-')||'-'
						)
						ELSE 0
					END AS column_order
				FROM
					pg_attribute AS col INNER JOIN
					pg_class AS t ON
						col.attrelid = t.oid INNER JOIN
					pg_namespace AS s ON
						t.relnamespace = s.oid INNER JOIN
					pg_constraint AS con ON
						col.attnum = ANY (con.conkey) AND
						con.conrelid = t.oid LEFT JOIN
					pg_class AS ft ON
						con.confrelid = ft.oid LEFT JOIN
					pg_namespace AS fs ON
						ft.relnamespace = fs.oid LEFT JOIN
					pg_attribute AS fc ON
						fc.attnum = ANY (con.confkey) AND
						ft.oid = fc.attrelid
				WHERE
					NOT col.attisdropped AND (
						con.contype = 'p' OR
						con.contype = 'f' OR
						con.contype = 'u'
					)
				) UNION (
					SELECT
						LOWER(n.nspname) AS \"schema\",
						LOWER(t.relname) AS \"table\",
						ic.relname AS constraint_name,
						'unique' AS type,
						LOWER(col.attname) AS column,
						NULL AS foreign_schema,
						NULL AS foreign_table,
						NULL AS foreign_column,
						NULL AS on_delete,
						NULL AS on_update,
						CASE
							WHEN ind.indkey IS NOT NULL THEN position(
								'-'||col.attnum||'-' in '-'||array_to_string(ind.indkey, '-')||'-'
							)
							ELSE 0
						END AS column_order
					FROM
						pg_class AS t INNER JOIN
						pg_index AS ind ON
							ind.indrelid = t.oid INNER JOIN
						pg_namespace AS n ON
							t.relnamespace = n.oid INNER JOIN
						pg_class AS ic ON
							ind.indexrelid = ic.oid LEFT JOIN
						pg_constraint AS con ON
							con.conrelid = t.oid AND
							con.contype = 'u' AND
							con.conname = ic.relname INNER JOIN
						pg_attribute AS col ON
							col.attrelid = t.oid AND
							col.attnum = ANY (ind.indkey)
					WHERE
						n.nspname NOT IN ('pg_catalog', 'pg_toast') AND
						indisunique = TRUE AND
						indisprimary = FALSE AND
						con.oid IS NULL AND
						0 != ALL ((ind.indkey)::int[])
				) ORDER BY 1, 2, 4, 3, 11"
			);

			$last_name  = '';
			$last_table = '';
			$last_type  = '';

			foreach ($result as $row) {
				if ($row['constraint_name'] != $last_name) {
					if ($last_name) {
						if ($last_type == 'foreign' || $last_type == 'unique') {
							$keys[$last_table][$last_type][] = $temp;

						} else {
							$keys[$last_table][$last_type] = $temp;
						}
					}

					$temp = array();

					if ($row['type'] == 'foreign') {
						$temp['column']         = $row['column'];
						$temp['foreign_table']  = $row['foreign_table'];

						if ($row['foreign_schema'] != 'public') {
							$temp['foreign_table'] = $row['foreign_schema'] . '.' . $temp['foreign_table'];
						}

						$temp['foreign_column'] = $row['foreign_column'];
						$temp['on_delete']      = 'no_action';
						$temp['on_update']      = 'no_action';

						if (!empty($row['on_delete'])) {
							$temp['on_delete'] = $row['on_delete'];
						}

						if (!empty($row['on_update'])) {
							$temp['on_update'] = $row['on_update'];
						}

					} else {
						$temp[] = $row['column'];
					}

					$last_table = $this->normalizeTable($row['schema'] . '.' . $row['table']);
					$last_name  = $row['constraint_name'];
					$last_type  = $row['type'];

				} else {
					$temp[] = $row['column'];
				}
			}

			if (isset($temp)) {
				if ($last_type == 'foreign' || $last_type == 'unique') {
					$keys[$last_table][$last_type][] = $temp;

				} else {
					$keys[$last_table][$last_type] = $temp;
				}
			}

			print_r($keys);

			$this->keys[$alias] = $keys;
		}



		/**
		 *
		 */
		protected function resolveRoutes($connection)
		{
			$alias = $connection->getAlias();

			if (isset($this->routes[$alias])) {
				return;
			}

			$this->resolveKeysAndIndexes($connection);

			$routes = array();
			$tables = $this->resolveRepositories($connection);

			foreach ($tables as $table) {
				$routes[$table] = [
					'hasMany'       => [],
					'hasManyUnique' => [],
					'hasOne'        => [],
					'hasOneUnique'  => []
				];
			}

			foreach ($tables as $table) {
				foreach ($this->keys[$alias][$table]['foreign'] as $i => $key) {
					$ltable  = $table;
					$lcolumn = $key['column'];
					$ftable  = $key['foreign_table'];
					$fcolumn = $key['foreign_column'];
					$lkeys   = $this->keys[$alias][$table];
					$rkeys   = $this->keys[$alias][$ftable];
					$right   = 'many';
					$left    = 'many';

					//
					// *-to-one
					//

					if (count($rkeys['primary']) == 1 && $fcolumn == $rkeys['primary'][0]) {
						$right = 'one';

					} else {
						foreach ($rkeys['unique'] as $rukey) {
							if (count($rukey) == 1 && $fcolumn == $rukey[0]) {
								$right = 'one';
							}
						}
					}

					//
					// one-to-*
					//

					if (count($lkeys['primary']) == 1 && $lcolumn == $lkeys['primary'][0]) {
						$left = 'one';

					} else {
						foreach ($lkeys['unique'] as $ukey) {
							if (count($ukey) == 1 && $lcolumn == $ukey[0]) {
								$left = 'one';
							}
						}
					}

					switch($left . '-to-' . $right) {
						case 'many-to-one':
							$routes[$table]['hasOne'][]         = [$ftable => [$lcolumn => $fcolumn]];
							$routes[$ftable]['hasManyUnique'][] = [$table  => [$fcolumn => $lcolumn]];
							break;

						case 'one-to-one':
							$routes[$table]['hasOneUnique'][]  = [$ftable => [$lcolumn => $fcolumn]];
							$routes[$ftable]['hasOneUnique'][] = [$table  => [$fcolumn => $lcolumn]];
							break;

						case 'one-to-many':
							$routes[$table]['hasManyUnique'][] = [$ftable => [$lcolumn => $fcolumn]];
							$routes[$ftable]['hasOne'][]       = [$table => [$fcolumn => $lcolumn]];

							break;
					}
				}
			}


			foreach ($tables as $table) {
				foreach ($routes[$table]['hasManyUnique'] as $mroute) {
					$ftable = key($mroute);

					foreach ($routes[$ftable]['hasOne'] as $froute) {
						if (key($froute) == $table) {
							continue;
						}

						$routes[$table]['hasMany'][] = array_merge($mroute, $froute);
					}
				}
			}

			$this->routes[$alias] = $routes;
		}


		/**
		 *
		 */
		protected function resolveColumns($connection)
		{
			$alias = $connection->getAlias();

			if (isset($this->columns[$alias])) {
				return;
			}

			$tables = $this->resolveRepositories($connection);

			foreach ($tables as $table) {

				if (strpos($table, '.') === FALSE) {
					$schema = static::DEFAULT_SCHEMA;
				} else {
					list($schema, $table) = explode('.', $table);
				}

				$columns = array();
				$result  = $connection->execute(
					"SELECT
						LOWER(pg_attribute.attname)                                AS column,
						format_type(pg_attribute.atttypid, pg_attribute.atttypmod) AS data_type,
						pg_attribute.attnotnull                                    AS not_null,
						pg_attrdef.adsrc                                           AS default,
						pg_get_constraintdef(pg_constraint.oid)                    AS constraint,
						col_description(pg_class.oid, pg_attribute.attnum)         AS comment
					FROM
						pg_attribute LEFT JOIN
						pg_class ON pg_attribute.attrelid = pg_class.oid LEFT JOIN
						pg_namespace ON pg_class.relnamespace = pg_namespace.oid LEFT JOIN
						pg_type ON pg_type.oid = pg_attribute.atttypid LEFT JOIN
						pg_constraint ON pg_constraint.conrelid = pg_class.oid AND
										 pg_attribute.attnum = ANY (pg_constraint.conkey) AND
										 pg_constraint.contype = 'c' LEFT JOIN
						pg_attrdef ON pg_class.oid = pg_attrdef.adrelid AND
									  pg_attribute.attnum = pg_attrdef.adnum
					WHERE
						NOT pg_attribute.attisdropped AND
						LOWER(pg_class.relname) = {{1}} AND
						LOWER(pg_namespace.nspname) = {{2}} AND
						pg_type.typname NOT IN ('oid', 'cid', 'xid', 'cid', 'xid', 'tid')
					ORDER BY
						pg_attribute.attnum,
						pg_constraint.contype
					", [
						1 => strtolower($table),
						2 => strtolower($schema)
					]
				);

				foreach ($result as $row) {
					$column = $row['column'];
					$info   = [
						'default' => NULL,
						'auto'    => FALSE
					];

					preg_match(static::REGEX_DATA_TYPE, $row['data_type'], $column_data_type);

					foreach (static::$incomingDataTypes as $data_type => $mapped_data_type) {
						if (stripos($column_data_type[1], $data_type) === 0) {
							$info['type'] = $mapped_data_type;

							if (isset($max_min_values[$data_type])) {
								$info['min_value'] = static::$maxMinValues[$data_type]['min'];
								$info['max_value'] = static::$maxMinValues[$data_type]['max'];
							}

							break;
						}
					}

					if (!isset($info['type'])) {
						$info['type'] = $column_data_type[1];
					}

					if ($info['type'] == 'blob' || $info['type'] == 'text') {
						$info['max_length'] = 1073741824;
					}

					if ($info['type'] == 'float' && isset($column_data_type[3]) && strlen($column_data_type[3]) > 0) {
						$info['decimal_places'] = (int) $column_data_type[3];
						$before_digits          = str_pad('', $column_data_type[2] - $info['decimal_places'], '9');
						$after_digits           = str_pad('', $info['decimal_places'], '9');
						$max_min                = $before_digits . ($after_digits ? '.' : '') . $after_digits;
						$info['min_value']      = -$max_min;
						$info['max_value']      = $max_min;
					}

					//
					// Handle the special data for varchar fields
					//

					if (in_array($info['type'], ['char', 'varchar'])) {
						$info['max_length'] = !empty($column_data_types[2])
							? $column_data_types[2]
							: 1073741824;
					}

					//
					// In PostgreSQL, a UUID can be the 32 digits, 32 digits plus 4 hyphens or 32
					// digits plus 4 hyphens and 2 curly braces
					//

					if ($row['data_type'] == 'uuid') {
						$info['max_length'] = 38;
					}


					//
					// Handle default values and serial data types
					//

					if ($info['type'] == 'integer' && stripos($row['default'], 'nextval(') !== FALSE) {
						$info['auto']    = TRUE;

					} elseif ($row['default'] !== NULL) {
						$info['auto'] = FALSE;

						if (preg_match('#^NULL::[\w\s]+$#', $row['default'])) {
							$info['default'] = NULL;

						} elseif ($row['default'] == 'now()') {
							$info['default'] = 'CURRENT_TIMESTAMP';

						} elseif ($row['default'] == "('now'::text)::date") {
							$info['default'] = 'CURRENT_DATE';

						} elseif ($row['default'] == "('now'::text)::time with time zone") {
							$info['default'] = 'CURRENT_TIME';

						} else {
							$info['default'] = preg_replace("/^'(.*)'::[a-z ]+\$/iD", '\1', $row['default']);
							$info['default'] = str_replace("''", "'", $info['default']);

							if ($info['type'] == 'boolean') {
								$info['default'] = ($info['default'] == 'false' || !$info['default'])
									? FALSE
									: TRUE;
							}
						}

					}

					$info['comment']  = $row['comment'];
					$info['nullable'] = $row['not_null'] != 't';
					$columns[$column] = $info;
				}

				if ($schema == static::DEFAULT_SCHEMA) {
					$this->columns[$alias][$table] = $columns;
				} else {
					$this->columns[$alias][$schema . '.' . $table] = $columns;
				}
			}

			return;
		}
	}
}
