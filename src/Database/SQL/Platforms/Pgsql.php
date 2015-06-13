<?php namespace Redub\Database\SQL
{
	/**
	 *
	 */
	class Pgsql extends AbstractPlatform
	{
		const DEFAULT_SCHEMA  = 'public';
		const PLATFORM_NAME   = 'Pgsql';
		const REGEX_DATA_TYPE = '#([\w ]+)\s*(?:\(\s*(\d+)(?:\s*,\s*(\d+))?\s*\))?#';

		static protected $dataTypes = [
			'boolean'			=> 'boolean',
			'smallint'			=> 'integer',
			'int'				=> 'integer',
			'bigint'			=> 'integer',
			'serial'			=> 'integer',
			'bigserial'			=> 'integer',
			'timestamp'			=> 'timestamp',
			'date'				=> 'date',
			'time'				=> 'time',
			'uuid'              => 'varchar',
			'character varying'	=> 'varchar',
			'character'			=> 'char',
			'real'				=> 'float',
			'double'			=> 'float',
			'numeric'			=> 'float',
			'bytea'				=> 'blob',
			'text'				=> 'text',
			'mediumtext'		=> 'text',
			'longtext'			=> 'text',
			'point'             => 'varchar',
			'line'              => 'varchar',
			'lseg'              => 'varchar',
			'box'               => 'varchar',
			'path'              => 'varchar',
			'polygon'           => 'varchar',
			'circle'            => 'varchar'
		];

		/**
		 * Quotes a string.  This does not do any additional escaping of existing quotes
		 *
		 * @static
		 * @access protected
		 * @param string $string The string to quote
		 * @return string The quoted string
		 */
		static protected function quote($string)
		{
			return '"' . $string . '"';
		}


		/**
		 *
		 */
		static public function escapeIdentifier($name, $prepared = FALSE)
		{
			if ($prepared) {
				// return $name;
			}

			$parts = explode('.', $name);
			$parts = array_map([__CLASS__, 'quote'], $parts);

			return implode('.', $parts);
		}


		/**
		 *
		 */
		public function resolveFields($connection, $table)
		{
			return array_keys($this->resolveTableColumnInfo($connection, $table));
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
		protected function resolveTableColumnInfo($connection, $table)
		{
			$schema      = static::DEFAULT_SCHEMA;
			$alias       = $connection->getAlias();
			$column_info = array();

			if (!isset($this->columnInfo[$alias])) {
				$this->columnInfo[$alias] = array();

			} elseif (isset($this->columnInfo[$alias][$table])) {
				return $this->columnInfo[$alias][$table];
			}

			if (strpos($table, '.') !== FALSE) {
				list ($schema, $table) = explode('.', $table);
			}

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

				foreach (static::$dataTypes as $data_type => $mapped_data_type) {
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
					$info['auto_increment'] = TRUE;

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

				$info['comment']      = $row['comment'];
				$info['not_null']     = $row['not_null'] == 't';
				$column_info[$column] = $info;
			}

			return $this->columnInfo[$alias][$schema . '.' . $table] = $column_info;
		}
	}
}
