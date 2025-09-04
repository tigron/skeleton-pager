<?php
/**
 * trait: Page
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 */

namespace Skeleton\Pager;

use Skeleton\Database\Database;
use Skeleton\Pager\Sql\Join;

trait Page {

	private static $objects_cache = [ 'extra_conditions' => null, 'extra_joins' => null, 'objects' => null ];

	/**
	 * Get paged
	 *
	 * @access public
	 * @param string $sort
	 * @param string $direction
	 * @param int $page
	 * @param int $all
	 * @param array $extra_conditions
	 * @param array $extra_joins
	 */
	public static function get_paged($sort = null, $direction = 'asc', $page = 1, $extra_conditions = [], $all = false, $extra_joins = [], $per_page = null) {
		$db = self::trait_get_database();
		$table = self::trait_get_database_table();
		$field_id = self::trait_get_table_field_id();
		$where = self::trait_get_search_where($extra_conditions, $extra_joins);
		$joins = self::trait_get_link_tables();

		if ($sort === null) {
			$escaped_sort = $db->quote_identifier($table) . '.' . $db->quote_identifier(self::trait_get_table_field_id());
		} else {
			if (strpos($sort, '`') === false && strpos($sort, '"') === false) {
				if (strpos($sort, '.') === false) {
					$escaped_sort = $db->quote_identifier($sort);
				} else {
					$parts = explode('.', $sort);

					foreach ($parts as $key => $value) {
						$parts[$key] = $db->quote_identifier($value);
					}

					$escaped_sort = implode('.', $parts);
				}
			}
		}

		$object = new \ReflectionClass(self::class);
		if (is_callable($sort)) {
			$sorter = 'object';
		} elseif ($sort !== null && $object->hasMethod($sort)) {
			$sorter = 'object';
		} else {
			$sorter = 'db';
		}
		if ($sorter == 'db') {
			foreach ($extra_conditions as $key => $extra_condition) {
				if ($key == '%search%') {
					continue;
				} elseif (strpos($key, '.') === false) {
					if (is_callable($key) or $object->hasMethod($key)) {
						$sorter = 'object';
						break;
					}
				}
			}
		}

		if (!$all) {
			if ($per_page === null) {
				$limit = Config::$items_per_page;
			} else {
				$limit = $per_page;
			}
		} else {
			$limit = 1000;
		}

		if ($page < 1) {
			$page = 1;
		}

		if (strtolower($direction) != 'asc') {
			$direction = 'desc';
		}

		$sql = 'SELECT DISTINCT ' . $db->quote_identifier($table) . '.' . $db->quote_identifier(self::trait_get_table_field_id()) . ' as id ';

		// PostgreSQL and more recent versions of MySQL require the fields used in the ORDER BY clause to be in the SELECT list as well
		if ($sorter == 'db' && $escaped_sort !== $db->quote_identifier($table) . '.' . $db->quote_identifier(self::trait_get_table_field_id())) {
			$sql .= ', ' . $escaped_sort . ' as sort_id ';
		}

		$sql .= "\n";

		$sql .= 'FROM ' . $db->quote_identifier($table) . "\n";

		/**
		 * Automatic join: Join if a condition or a sort is set
		 *
		 * 1) Find the tables where a condition is set
		 * 2) Find the table where a sort is set on
		 * 3) Remove all joins that are not in 1 and 2
		 */
		$table_joins = self::trait_get_joins();
		$table_joins = array_merge($table_joins, $extra_joins);

		$condition_joins = [];
		foreach ($extra_conditions as $field => $condition) {
			if ($field != '%search%') {
				$condition_table = substr($field, 0, strpos($field, '.'));
				$condition_joins[$condition_table] = $condition_table;
			}
			if ($field == '%search%') {
				if (count($condition[1]) == 0) {
					$condition_joins['*'] = '*';
				} else {
					foreach ($condition[1] as $search_condition_field) {
						$condition_table = substr($search_condition_field, 0, strpos($search_condition_field, '.'));
						$condition_joins[$condition_table] = $condition_table;
					}
				}
			}
		}

		if ($sort !== null) {
			$sort_condition_table = substr($sort, 0, strpos($sort, '.'));
			$condition_joins[$sort_condition_table] = $sort_condition_table;
		}

		do {
			$remove_count = 0;
			foreach ($table_joins as $key => $table_join) {
				if (isset($condition_joins['*'])) {
					continue;
				}

				$remove = true;
				if (isset($condition_joins[ $table_join->get_remote_table()])) {
					$remove = false;
				}
				if ($remove == true) {
					foreach ($table_joins as $table_join2) {
						$table_name = $table_join2->get_local_field();
						if (strpos($table_name, '.') > 0) {
							$table_name = explode('.', $table_name)[0];
							if ($table_name == $table_join->get_remote_table()) {
								$remove = false;
							}
						}
					}
				}

				if ($remove) {
					$remove_count++;
					unset($table_joins[$key]);
				}
			}
		} while ($remove_count > 0);

		foreach ($table_joins as $table_join) {
			$sql .= $table_join;
		}

		/**
		 * End of automatic join
		 */

		$sql .= 'WHERE 1=1 ' . $where . "\n";
		if ($sorter == 'db') {
			/*
			// I don't think this is necessary anymore
			if (strpos($sort, '.') === false AND $sort != 1) {
				$sort = $db->quote_identifier($table) . '.' . $db->quote_identifier($sort);
			}
			*/
			$sql .= 'ORDER BY ' . $escaped_sort . ' ' . $direction . ', ' . $db->quote_identifier($table) . '.' . $db->quote_identifier($field_id);
		}

		if ($all !== true AND $sorter == 'db') {
			$sql .= ' LIMIT ' . $limit . ' OFFSET ' . ($page-1)*$limit;
		}

		$rows = $db->get_all($sql);

		$objects = [];
		foreach ($rows as $row) {
			$objects[] = self::get_by_id($row['id']);
		}

		foreach ($extra_conditions as $fieldname => $conditions) {
			foreach ($conditions as $condition) {
				foreach ($objects as $key => $object) {
					if (!method_exists($object, $fieldname) or !is_callable([$object, $fieldname])) {
						continue;
					}

					try {
						$result = call_user_func_array([$object, $fieldname], []);

						if ($condition->evaluate($result)) {
							continue;
						}
					} catch (Exception $e) {
						continue;
					}

					unset($objects[$key]);
				}
			}
		}

		if ($sorter == 'object') {
			$objects = Util::object_sort($objects, $sort, $direction);
			self::$objects_cache['extra_conditions'] = $extra_conditions;
			self::$objects_cache['extra_joins'] = $extra_joins;
			self::$objects_cache['objects'] = $objects;
			$objects = array_slice($objects, ($page-1)*$limit, $limit);
		}

		return $objects;
	}

	/**
	 * Count the number of results
	 *
	 * @access public
	 * @param array $extra_conditions
	 * @param array $extra_joins
	 * @return int $count
	 */
	public static function count($extra_conditions = [], $extra_joins = []) {
		return self::trait_get_aggregate('count', $extra_conditions, $extra_joins);
	}

	/**
	 * Get the sum for a given column
	 *
	 * @access public
	 * @param array $extra_conditions
	 * @param array $extra_joins
	 * @return int $count
	 */
	public static function sum($field, $extra_conditions = [], $extra_joins = []) {
		return self::trait_get_aggregate('sum', $extra_conditions, $extra_joins, ['field' => $field]);
	}

	/**
	 * Get the table definition for a given table
	 *
	 * This also adds some additional data to the information, such as a
	 * simplified version of the type.
	 *
	 * @access private
	 * @param string $table
	 * @return array $definitions
	 */
	private static function trait_get_table_definition($table) {
		$db = self::trait_get_database();
		$definitions = $db->get_table_definition($table);
		$indexes = $db->get_table_indexes($table);

		foreach ($definitions as $key => $definition) {
			// Define a 'simple_type', only telling us what kind of data
			// we are dealing with.
			$definitions[$key]['simple_type'] = null;

			if (strpos($definition['Type'], '(') !== false) {
				$type = substr($definition['Type'], 0, strpos($definition['Type'], '('));
			} else {
				$type = $definition['Type'];
			}

			switch ($type) {
				case 'text':
				case 'tinytext':
				case 'mediumtext':
				case 'longtext':
				case 'varchar':
				case 'enum':
					$definitions[$key]['simple_type'] = 'text';
					break;
				case 'date':
				case 'datetime':
				case 'time':
					$definitions[$key]['simple_type'] = 'date';
					break;
				case 'tinyint':
				case 'decimal':
				case 'double':
				case 'mediumint':
				case 'int':
				case 'bigint':
					$definitions[$key]['simple_type'] = 'number';
					break;
			}

			// Find out if we have some kind of index enabled on this field
			$definitions[$key]['has_index'] = false;
			$definitions[$key]['index_type'] = null;

			foreach ($indexes as $index) {
				if ($index['Column_name'] == $definition['Field']) {
					$definitions[$key]['has_index'] = true;
					$definitions[$key]['index_type'] = strtolower($index['Index_type']);
				}
			}
		}

		return $definitions;
	}

	/**
	 * Find out how to compare the field and the given value
	 *
	 * @access private
	 * @param string $field
	 * @param string $value
	 * @param array $definition
	 * @return string $where
	 */
	private static function trait_get_comparison($field, $value, $definition) {
		$db = self::trait_get_database();
		$where = '';

		if ($definition['simple_type'] == 'text') {
			$where = 'OR ' . $field . ' LIKE \'%' . $db->quote($value, false) . '%\' ' . "\n\t";
		} elseif ($definition['simple_type'] == 'number' and is_numeric($value)) {
			$where = 'OR ' . $field . ' = \'' . $db->quote($value, false) . '\' ' . "\n\t";
		}

		return $where;
	}

	/**
	 * Get where clause for paging
	 *
	 * @access public
	 * @param array $extra_conditions
	 * @param array $extra_joins
	 */
	private static function trait_get_search_where($extra_conditions = [], $extra_joins = []) {
		$db = self::trait_get_database();
		$table = self::trait_get_database_table();
		$field_id = self::trait_get_table_field_id();
		$definitions = self::trait_get_table_definition($table);
		$joins = self::trait_get_link_tables();

		$where = '';

		$object = new \ReflectionClass(self::class);

		foreach ($extra_conditions as $key => $condition_array) {
			if ($key == '%search%' OR is_callable($key) OR $object->hasMethod($key)) {
				continue;
			}

			// Ignore the language setting for object text.
			if ($key == 'object_text.language_id') {
				continue;
			}

			foreach ($condition_array as $condition) {
				$where .= 'AND ' . $condition;
			}
		}

		if (isset($extra_conditions['%search%']) AND $extra_conditions['%search%'][0] != '') {
			$where .= 'AND (1 ';

			$ec_search = explode(' ', trim($extra_conditions['%search%'][0]));

			foreach ($ec_search as $element) {
				if (count($extra_conditions['%search%'][1]) == 0) {
					$definitions = self::trait_get_table_definition($table);
					$where .= ' AND (0 ';

					foreach ($definitions as $definition) {
						$where .= self::trait_get_comparison($table . '.' . $definition['Field'], $element, $definition);
					}

					foreach ($joins as $join) {
						$definitions = self::trait_get_table_definition($join);


						foreach ($definitions as $definition) {
							$where .= self::trait_get_comparison($join . '.' . $definition['Field'], $element, $definition);
						}
					}

					foreach ($extra_joins as $extra_join) {
						$definitions = self::trait_get_table_definition($extra_join->get_remote_table());

						foreach ($definitions as $definition) {
							$where .= self::trait_get_comparison($extra_join->get_remote_table() . '.' . $definition['Field'], $element, $definition);
						}
					}
				} else {
					$where .= ' AND (0 ';
					$definitions = [];

					foreach ($extra_conditions['%search%'][1] as $field) {
						list($condition_table, $condition_field) = explode('.', $field);
						if (!isset($definitions[$condition_table])) {
							$definitions[$condition_table] = self::trait_get_table_definition($condition_table);
						}

						foreach ($definitions[$condition_table] as $definition) {
							if ($definition['Field'] == $condition_field) {
								$where .= self::trait_get_comparison($field, $element, $definition);
							}
						}

					}
				}

				if (isset(self::$object_text_fields) AND count(self::$object_text_fields) > 0) {
					$where .= 'OR ' . $table . '.' . $field_id . ' IN ( ';
					$where .= ' SELECT object_id FROM object_text ';
					$where .= ' WHERE object_id=' . $table . '.' . $field_id;
					$where .= ' AND object_text.classname LIKE "' . self::class . '"';
					if (isset($extra_conditions['object_text.language_id'])) {
						$where .= ' AND object_text.language_id=' . $extra_conditions['object_text.language_id'][0]->get_value();
					}
					$where .= ' AND content LIKE "%' . $db->quote($element, false) . '%"';
					$where .= ')';
				}

				$where .= ') ';
			}

			$where .= ') ' . "\n";
		}

		if (strlen($where) > 0) {
			return "\n\t" . $where;
		} else {
			return '';
		}
	}

	/**
	 * Calculate an aggregate
	 *
	 * @access public
	 * @param string $type
	 * @param array $extra_conditions
	 * @param array $extra_joins
	 * @param array $extra_parameters
	 * @return int $count
	 */
	private static function trait_get_aggregate($type, $extra_conditions = [], $extra_joins = [], $extra_parameters = []) {
		$db = self::trait_get_database();
		$table = self::trait_get_database_table();
		$where = self::trait_get_search_where($extra_conditions, $extra_joins);
		$joins = self::trait_get_link_tables();

		$join_mandatory = false;

		// testing if db or object mode
		$sorter = 'db';
		$object = new \ReflectionClass(self::class);
		foreach ($extra_conditions as $key => $extra_condition) {
			foreach ($extra_conditions as $key => $extra_condition) {
				if ($key == '%search%') {
					continue;
				} elseif (strpos($key, '.') === false) {
					if (is_callable($key) or $object->hasMethod($key)) {
						$sorter = 'object';
						break;
					}
				}
			}
		}
		if ($sorter == 'object') {
			// if mode is object and objects are not cached or extra_conditions|extra_joins are not equal to what is cached
			// then we request the get_paged() again to get the good objects
			if (isset(self::$objects_cache['objects']) == false or self::$objects_cache['objects'] == null or
				count(Util::array_diff_assoc_recursive($extra_conditions, self::$objects_cache['extra_conditions'])) > 0 or
				count(Util::array_diff_assoc_recursive($extra_joins, self::$objects_cache['extra_joins'])) > 0) {
				self::get_paged(null, 'asc', 1, $extra_conditions, true, $extra_joins);
			}
		}

		switch ($type) {
			case 'count':
				if ($sorter == 'object') {
					return count(self::$objects_cache['objects']);
				}
				$sql = '(SELECT COUNT(DISTINCT ' . $db->quote_identifier($table) . '.' . $db->quote_identifier(self::trait_get_table_field_id()) . ') ';
				break;
			case 'sum':
				if (!isset($extra_parameters['field'])) {
					throw new Exception('Aggregate sum needs a field');
				}

				if ($sorter == 'object') {
					$sum = 0;
					$sum_field = $extra_parameters['field'];
					foreach (self::$objects_cache['objects'] as $object) {
						$sum += $object->$sum_field;
					}
					return $sum;
				}

				list($field_table, $field) = explode('.', $extra_parameters['field']);

				if ($field_table == $table) {
					$sql = 'SELECT SUM(' . $extra_parameters['field'] . ') FROM ' . $db->quote_identifier($table) . ' WHERE id IN ( SELECT ' . $db->quote_identifier($table) . '.' . $db->quote_identifier('id') ;
				} else {
					$sql = '(SELECT SUM(DISTINCT ' . $extra_parameters['field'] . ') ';
				}

				$join_mandatory = true;
				break;
			default:
				throw new Exception('Unsupported aggregate');
		}

		$sql .=  "\n" . 'FROM ' . $db->quote_identifier($table) . ' ' . "\n";

		/**
		 * Automatic join: Join if a condition or a sort is set
		 *
		 * 1) Find the tables where a condition is set
		 * 2) Find the table where a sort is set on
		 * 3) Remove all joins that are not in 1 and 2
		 */
		$table_joins = self::trait_get_joins();
		$table_joins = array_merge($table_joins, $extra_joins);

		$condition_joins = [];
		foreach ($extra_conditions as $field => $condition) {
			if ($field != '%search%') {
				$condition_table = substr($field, 0, strpos($field, '.'));
				$condition_joins[$condition_table] = $condition_table;
			}
			if ($field == '%search%') {
				if (count($condition[1]) == 0) {
					$condition_joins['*'] = '*';
				} else {
					foreach ($condition[1] as $search_condition_field) {
						$condition_table = substr($search_condition_field, 0, strpos($search_condition_field, '.'));
						$condition_joins[$condition_table] = $condition_table;
					}
				}
			}
		}

		do {
			$remove_count = 0;
			foreach ($table_joins as $key => $table_join) {
				if (isset($condition_joins['*'])) {
					continue;
				}

				$remove = true;
				if (isset($condition_joins[ $table_join->get_remote_table()])) {
					$remove = false;
				}
				if ($remove == true) {
					foreach ($table_joins as $table_join2) {
						$table_name = $table_join2->get_local_field();
						if (strpos($table_name, '.') > 0) {
							$table_name = explode('.', $table_name)[0];
							if ($table_name == $table_join->get_remote_table()) {
								$remove = false;
							}
						}
					}
				}

				if ($remove) {
					$remove_count++;
					unset($table_joins[$key]);
				}
			}
		} while ($remove_count > 0);

		foreach ($table_joins as $table_join) {
			$sql .= $table_join;
		}

		$sql .= 'WHERE 1=1 ' . $where . ')';
		$count = $db->get_one($sql);

		return $count;
	}

	private static function trait_get_joins() {
		$db = self::trait_get_database();
		$table = self::trait_get_database_table();
		$fields = $db->get_columns($table);
		$tables = $db->get_tables();

		$joins = [];
		foreach ($fields as $field) {
			if (substr($field, -3) != '_id') {
				continue;
			}

			$remote_table = substr($field, 0, -3);

			if (in_array($remote_table, $tables)) {
				$joins[] = new Join($remote_table, 'id', $table . '.' . $field);
			}
		}
		return $joins;
	}
}
