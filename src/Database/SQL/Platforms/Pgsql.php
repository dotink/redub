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

		static protected $incomingDataTypes = [
			'boolean'			=> 'boolean',
			'smallint'			=> 'integer',
			'int'				=> 'integer',
			'bigint'			=> 'integer',
			'serial'			=> 'integer',
			'bigserial'			=> 'integer',
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

		protected $columns = array();

		protected $keys = array();

		protected $routes = array();


		/**
		 *
		 */
		static public function escapeIdentifier($name, $prepared = FALSE)
		{
			if ($prepared) {
				// return $name;
			}

			$parts = explode('.', $name);
			$parts = array_map(function($id) { return '"' . $id . '"'; }, $parts);

			return implode('.', $parts);
		}


		/**
		 *
		 */
		public function resolveFields($connection, $table)
		{
			return array_keys($this->resolveColumns($connection, $table));
		}


		/**
		 *
		 */
		public function resolveFieldInfo($connection, $table, $field, $type)
		{
			$info  = $this->resolveColumns($connection, $table);
			$alias = $connection->getAlias();

			if (!isset($info[$field])) {
				throw new Flourish\ProgrammerException(
					'Could not resolve column information for %s on table %s',
					$field,
					$table
				);
			}

			if (!isset($info[$field][$type])) {
				switch ($type) {
					case 'default': return NULL;
					case 'auto':    return FALSE;
					default:
						throw new Flourish\ProgrammerException(
							'Cannot get information on field "%s" for "%s", not available',
							$field,
							$type
						);
				}
			}

			return $info[$field][$type];
		}


		/**
		 *
		 */
		public function resolveIdentity($connection, $table)
		{
			$keys = $this->resolveKeysAndIndexes($connection, $table);

			return $keys['primary'];
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
			$keys = $this->resolveKeysAndIndexes($connection, $table);

			return $keys['unique'];
		}


		/**
		 *
		 */
		public function resolveRoutesToMany($connection, $table, $unique)
		{
			$routes = $this->resolveRoutes($connection, $table);

			return $unique
				? $routes['hasManyUnique']
				: $routes['hasMany'];
		}


		/**
		 *
		 */
		public function resolveRoutesToOne($connection, $table, $unique)
		{
			$routes = $this->resolveRoutes($connection, $table);

			return $unique
				? $routes['hasOneUnique']
				: $routes['hasOne'];
		}


		/**
		 *
		 */
		protected function resolveKeysAndIndexes($connection, $table)
		{
			$alias = $connection->getAlias();

			if (strpos($table, '.') === FALSE) {
				$table = static::DEFAULT_SCHEMA . '.' . $table;
			}

			if (isset($this->keys[$alias][$table])) {
				return $this->keys[$alias][$table];

			} else {
				$this->keys[$alias]         = array();
				$this->keys[$alias][$table] = array();
			}

			$keys = array();

			foreach ($this->resolveRepositories($connection) as $repository) {
				if (strpos($repository, '.') === FALSE) {
					$repository = static::DEFAULT_SCHEMA . '.' . $repository;
				}

				$keys[$repository]            = array();
				$keys[$repository]['primary'] = array();
				$keys[$repository]['unique']  = array();
				$keys[$repository]['foreign'] = array();
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

					$last_table = $row['schema'] . '.' . $row['table'];
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

			return $this->resolveKeysAndIndexes($connection, $table);
		}


		/**
		 *
		 */
		protected function resolveRoutes($connection, $table)
		{
			$alias  = $connection->getAlias();

			if (isset($this->routes[$alias][$table])) {
				return $this->routes[$alias][$table];

			} else {
				$this->routes[$alias]         = array();
				$this->routes[$alias][$table] = array();
			}

			$keys   = $this->resolveKeysAndIndexes($connection, $table);
			$routes = [
				'hasMany'       => [],
				'hasManyUnique' => [],
				'hasOne'        => [],
				'hasOneUnique'  => []
			];


			//
			// TODO: Implement
			//


			$this->routes[$alias][$table] = $routes;

			return $this->resolveRoutes($connection, $table);
		}


		/**
		 *
		 */
		protected function resolveColumns($connection, $table)
		{
			$alias  = $connection->getAlias();
			$schema = static::DEFAULT_SCHEMA;

			if (strpos($table, '.') !== FALSE) {
				list ($schema, $table) = explode('.', $table);
			}

			if (isset($this->columns[$alias][$table])) {
				return $this->columns[$alias][$table];

			} else {
				$this->columns[$alias]         = array();
				$this->columns[$alias][$table] = array();
			}

			$columns        = array();
			$max_min_values = [
				'smallint'  => ['min' => -32768,               'max' => 32767],
				'int'       => ['min' => -2147483648,          'max' => 2147483647],
				'bigint'    => ['min' => -9223372036854775808, 'max' => 9223372036854775807],
				'serial'    => ['min' => -2147483648,          'max' => 2147483647],
				'bigserial' => ['min' => -9223372036854775808, 'max' => 9223372036854775807]
			];

			$result = $connection->execute(
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
				$info   = array();
				$column = $row['column'];

				preg_match(static::REGEX_DATA_TYPE, $row['data_type'], $column_data_type);

				foreach (static::$incomingDataTypes as $data_type => $mapped_data_type) {
					if (stripos($column_data_type[1], $data_type) === 0) {
						$info['type'] = $mapped_data_type;

						if (isset($max_min_values[$data_type])) {
							$info['min_value'] = $max_min_values[$data_type]['min'];
							$info['max_value'] = $max_min_values[$data_type]['max'];
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
					$info['auto'] = TRUE;

				} elseif ($row['default'] !== NULL) {
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

			print_r($columns);

			$this->columns[$alias][$schema . '.' . $table] = $columns;

			return $this->resolveColumns($connection, $table);
		}
	}
}
