<?php

namespace Skeleton\Pager;

use \Skeleton\Pager\Config;
use \Skeleton\Pager\Sql\Condition;
use \Skeleton\Pager\Sql\Join;

class Pager {
	/**
	 * Classname
	 *
	 * @access private
	 * @var string $classname
	 */
	protected $classname;

	/**
	 * Options
	 *
	 * @access private
	 * @var array $options
	 */
	protected $options = [
		'conditions' => [],
		'sort' => null,
		'direction' => 'asc',
		'page' => 1,
		'joins' => [],
		'sort_permissions' => [],
		'conditions_restrictions' => [],
	];

	/**
	 * Items
	 *
	 * @access public
	 * @var array $items
	 */
	public $items = [];

	/**
	 * Item count
	 *
	 * @access public
	 * @var int $item_count
	 */
	public $item_count = 0;

	/**
	 * Interval
	 *
	 * @access private
	 * @var int $interval
	 */
	private $interval = 5;

	/**
	 * Constructor
	 *
	 * @access private
	 * @param $code
	 */
	public function __construct($classname = null) {
		if ($classname === null) {
			throw new \Exception('You must provide a classname');
		}
		if (!class_exists($classname)) {
			throw new \Exception('Pager creation for class ' . $classname . ' failed: Unknow classname');
		}
		if (!is_callable([$classname, 'get_paged'])) {
			throw new \Exception('Pager create for class ' . $classname . ' failed: method get_paged() is no callable');
		}
		if (!is_callable([$classname, 'count'])) {
			throw new \Exception('Pager create for class ' . $classname . ' failed: method count() is no callable');
		}

		$this->classname = $classname;
		$this->set_jump_to(Config::$jump_to);
		$this->set_per_page(Config::$items_per_page);
	}

	/**
	 * Set sort field
	 *
	 * @access public
	 * @param string $sort
	 */
	public function set_sort($sort) {
		$object = new \ReflectionClass($this->classname);
		if (is_callable($sort) === false AND $object->hasMethod($sort) === false) {
			$sort = $this->expand_field_name($sort);
		}
		$this->options['sort'] = $sort;
	}

	/**
	 * Get sort
	 *
	 * @access public
	 * @return string $sort
	 */
	public function get_sort() {
		return $this->options['sort'];
	}

	/**
	 * Set direction
	 *
	 * @access public
	 * @param string $direction
	 */
	public function set_direction($direction = 'asc') {
		$this->options['direction'] = $direction;
	}

	/**
	 * Get direction
	 *
	 * @access public
	 * @return string $direction
	 */
	public function get_direction() {
		return $this->options['direction'];
	}

	/**
	 * Add a sort permission
	 *
	 * @access public
	 * @param $column
	 * @param $database_field
	 */
	public function add_sort_permission($database_field) {
		$object = new \ReflectionClass($this->classname);
		if (is_callable($database_field) === false AND $object->hasMethod($database_field) === false) {
			$database_field = $this->expand_field_name($database_field);
		}
		$this->options['sort_permissions'][] = $database_field;
	}

	/**
	 * Set page
	 *
	 * @access public
	 * @param int $page
	 */
	public function set_page($page) {
		$this->options['page'] = $page;
	}

	/**
	 * Get page
	 *
	 * @access public
	 * @return int $page
	 */
	public function get_page() {
		return $this->options['page'];
	}

	/**
	 * Add condition
	 *
	 * @access public
	 * @param string $field
	 * @param string $comparison (optional)
	 * @param string $value
	 */
	public function add_condition() {
		$params = func_get_args();
		$conditions = $this->options['conditions'];

		$field = array_shift($params);
		$field = $this->expand_field_name($field);

		if (count($params) == 1) {
			$params = array_shift($params);
			$condition = new Condition($field, '=', $params);
		} else {
			$comparison = array_shift($params);
			$params = array_shift($params);
			$condition = new Condition($field, $comparison, $params);
		}

		$conditions[$field][] = $condition;

		$this->options['conditions'] = $conditions;
	}

	/**
	 * Add condition restriction
	 *
	 * @access public
	 * @param string $field
	 * @param string $comparison (optional)
	 * @param string $value
	 */
	public function add_condition_restriction($field, $comparison, $params) {
		$field = $this->expand_field_name($field);
		$this->options['conditions_restrictions'][] = new Condition($field, $comparison, $params);
	}

	/**
	 * Has condition
	 *
	 * @access public
	 * @param string $field
	 * @param string $comparison (optional)
	 * @param string $value
	 */
	public function has_condition() {
		$params = func_get_args();

		if (is_a($params, '\Skeleton\Pager\Sql\Condition')) {
			$condition = $params;
		} else {
			$field = array_shift($params);
			$field = $this->expand_field_name($field);

			if (count($params) == 1) {
				$condition = new Condition($field, '=', array_shift($params));
			} else {
				$condition = new Condition($field, array_shift($params), $params);
			}
		}

		foreach ($this->options['conditions'] as $cond_field => $stored_conditions) {
			if ($field != $cond_field) {
				continue;
			}

			foreach ($stored_conditions as $stored_condition) {
				if ($condition->equals($stored_condition)) {
					return true;
				}

			}
		}

		return false;
	}

	/**
	 * Add join
	 *
	 * @access public
	 * @param string $remote_table
	 * @param string $remote_id
	 * @param string $local_field
	 * @param array $extra_join_conditions
	 */
	public function add_join($remote_table, $remote_id, $local_field, $extra_conditions = []) {
		$local_field = $this->expand_field_name($local_field);

		$join = new Join($remote_table, $remote_id, $local_field);

		if (is_a($extra_conditions, '\Skeleton\Pager\Sql\Condition')) {
			$join->add_condition($extra_conditions);
		} else {
			foreach ($extra_conditions as $extra_condition) {
				$join->add_condition($extra_condition);
			}
		}

		$this->options['joins'][] = $join;
	}

	/**
	 * Add join
	 *
	 * @access public
	 * @param string $remote_table
	 * @param string $remote_id
	 * @param string $local_field
	 * @param array $extra_join_conditions
	 * @return bool
	 */
	public function has_join($remote_table, $remote_id, $local_field, $extra_conditions = []) {
		if (isset($this->options['joins']) == false) {
			return false;
		}
		foreach ($this->options['joins'] as $join) {
			if ($join->get_remote_table() == $remote_table && $join->get_remote_id() == $remote_id && $join->get_local_field() == $local_field) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Activate 'Jump to page'
	 *
	 * @access public
	 * @param bool $jump_to
	 */
	public function set_jump_to($jump_to) {
		$this->options['jump_to'] = $jump_to;
	}

	/**
	 * Get jump to
	 *
	 * @access public
	 * @return bool $jump_to
	 */
	public function get_jump_to() {
		return $this->options['jump_to'];
	}

	/**
	 * Set 'per page items'
	 *
	 * @access public
	 * @param int $per_page
	 */
	public function set_per_page($per_page) {
		$this->options['per_page'] = $per_page;
	}

	/**
	 * Get per page
	 *
	 * @access public
	 * @return bool $jump_to
	 */
	public function get_per_page() {
		return $this->options['per_page'];
	}

	/**
	 * Set a search
	 *
	 * @access public
	 * @param string $search
	 */
	public function set_search($search, $search_fields = []) {
		foreach ($search_fields as $key => $search_field) {
			$search_fields[$key] = $this->expand_field_name($search_field);
		}
		$this->options['conditions']['%search%'] = [ $search, $search_fields ];
	}

	/**
	 * Get search
	 *
	 * @access public
	 * @return string $search
	 */
	public function get_search() {
		if (isset($this->options['conditions']['%search%'])) {
			return $this->options['conditions']['%search%'][0];
		} else {
			return '';
		}
	}

	/**
	 * Get sum
	 *
	 * @access public
	 * @param string $field
	 */
	public function get_sum($field) {
		$field = $this->expand_field_name($field);
		return call_user_func_array([$this->classname, 'sum'], [$field, $this->options['conditions'], $this->options['joins']]);
	}

	/**
	 * Clear conditions
	 *
	 * @access public
	 */
	public function clear_conditions() {
		unset($this->options['conditions']);
		$this->options['conditions'] = [];
	}

	/**
	 * Clear condition
	 *
	 * @access public
	 * @param string $field
	 */
	public function clear_condition($field) {
		$field = $this->expand_field_name($field);
		unset($this->options['conditions'][$field]);
	}

	/**
	 * Get conditions
	 *
	 * @return array $conditions
	 */
	public function get_conditions() {
		return $this->options['conditions'];
	}

	/**
	 * Get classname
	 *
	 * @access public
	 * @return string $classname
	 */
	public function get_classname() {
		return $this->classname;
	}

	/**
	 * Get the pager options from a hash
	 *
	 * @access private
	 * @param string $hash
	 * @return array $options
	 */
	protected static function get_options_from_hash($hash) {
		$data = @gzdecode(base64_decode(rawurldecode($hash)));
		if ($data !== false) {
			$options = json_decode($data, true);
			//support for old hashes
			if (isset($options['per_page']) === false) {
				$options['per_page'] = Config::$items_per_page;
			}
		} else {
			$options = [
				'conditions' => [],
				'sort' => null,
				'direction' => 'asc',
				'page' => 1,
				'jump_to' => Config::$jump_to,
				'joins' => [],
				'sort_permissions' => [],
				'classname' => null,
				'per_page' => Config::$items_per_page,
			];
		}

		if (isset($options['conditions']) and is_array($options['conditions'])) {
			$conditions = [];
			foreach ($options['conditions'] as $condition_key => $condition) {
				foreach ($condition as $setting_key => $setting) {
					if ($condition_key === '%search%') {
						$conditions[$condition_key][$setting_key] = $setting;
					} else {
						$conditions[$condition_key][$setting_key] = new Condition($setting['local_field'], $setting['comparison'], $setting['value'], $setting['local_function']);
					}
				}
			}

			$options['conditions'] = $conditions;
		}

		if (isset($options['joins']) and is_array($options['joins'])) {
			$joins = [];
			foreach ($options['joins'] as $join) {
				$joins[] = new Join($join['remote_table'], $join['remote_id'], $join['local_field'], $join['conditions']);
			}

			$options['joins'] = $joins;
		}

		return $options;
	}

	/**
	 * Create options hash
	 *
	 * @access private
	 * @param array $conditions
	 * @param int $page
	 * @param int $sort
	 * @param string $direction
	 */
	public function create_options_hash($conditions = null, $page = null, $sort = null, $direction = null, $joins = null, $per_page = null) {
		if ($conditions === null) {
			$conditions = $this->options['conditions'];
		}

		if ($page === null) {
			$page = $this->options['page'];
		}

		if ($sort === null) {
			$sort = $this->options['sort'];
		}

		if ($direction === null) {
			$direction = $this->options['direction'];
		}

		if ($per_page === null) {
			$per_page = $this->options['per_page'];
		}

		if (is_array($conditions)) {
			$flat_conditions = [];

			foreach ($conditions as $condition_key => $condition) {
				foreach ($condition as $setting_key => $setting) {
					if ($condition_key === '%search%') {
						$flat_conditions[$condition_key][$setting_key] = $setting;
					} else {
						$flat_conditions[$condition_key][$setting_key] = [
							'local_field' => $setting->get_local_field(),
							'local_function' => $setting->get_local_function(),
							'comparison' => $setting->get_comparison(),
							'value' => $setting->get_value(),
						];
					}
				}
			}
		} else {
			$flat_conditions = $conditions;
		}

		$joins = [];
		foreach ($this->options['joins'] as $join) {
			$joins[] = [
				'remote_table' => $join->get_remote_table(),
				'remote_id' => $join->get_remote_id(),
				'local_field' => $join->get_local_field(),
				'conditions' => $join->get_conditions(),
			];
		}

		$options = [
			'classname' => $this->classname,
			'conditions' => $flat_conditions,
			'page' => $page,
			'sort' => $sort,
			'direction' => $direction,
			'joins' => $joins,
			'per_page' => $per_page,
		];

		$data = json_encode($options);
		$hash = rawurlencode(base64_encode(gzencode($data)));

		return $hash;
	}

	/**
	 * Paginate the results
	 *
	 * @access private
	 */
	public function page($all = false) {
		if ($this->options['sort'] === null AND isset($this->options['sort_permissions']) AND count($this->options['sort_permissions']) > 0) {
			reset($this->options['sort_permissions']);
			$this->options['sort'] = current($this->options['sort_permissions']);
		}

		// Check if we are allowed to sort at all
		if ($this->options['sort'] != null AND !is_callable($this->options['sort']) AND !in_array($this->options['sort'], $this->options['sort_permissions'])) {
			throw new \Exception('Sorting not allowed for field ' . $this->options['sort']);
		}

		// Check if all the condition restrictions are fulfulled
		if (isset($this->options['conditions_restrictions'])) {
			foreach ($this->options['conditions_restrictions'] as $condition_restriction) {
				$found = false;
				foreach ($this->get_conditions() as $condition) {
					$condition = array_shift($condition);
					if ($condition->equals($condition_restriction)) {
						$found = true;
						break;
					}
				}
				if ($found === false) {
					throw new \Exception('Permission denied');
				}
			}
		}

		$sort = $this->options['sort'];

		$this->options['all'] = $all;

		$params = [
			$sort,
			$this->options['direction'],
			$this->options['page'],
			$this->options['conditions'],
			$this->options['all'],
			$this->options['joins'],
			$this->options['per_page']
		];

		$this->items = call_user_func_array([$this->classname, 'get_paged'], $params);
		$this->item_count = call_user_func_array([$this->classname, 'count'], [$this->options['conditions'], $this->options['joins']]);

		// The requested page is empty, so we have probably passed the last item
		// page to the last page instead
		if (count($this->items) === 0 && $this->item_count > 0) {
			$this->options['page'] = ceil($this->item_count / $this->options['per_page']);
			$params[2] = $this->options['page'];
			$this->items = call_user_func_array([$this->classname, 'get_paged'], $params);
		}
	}

	/**
	 * expand field name
	 * If a fieldname without a '.' is given, it will be prepended with the table name
	 *
	 * @access private
	 * @param $string $field_name
	 * @return string $expanded_field_name
	 */
	protected function expand_field_name($field_name) {
		if (strpos($field_name, '.') !== false) {
			return $field_name;
		}

		$classname = $this->classname;
		$object = new \ReflectionClass($classname);
		if (is_callable($field_name) === true OR $object->hasMethod($field_name) === true) {
			return $field_name;
		}

		return $classname::trait_get_database_table() . '.' . $field_name;
	}

	/**
	 * Get from options_hash
	 *
	 * @access public
	 * @param string $options_hash
	 * @return Web_Pager $pager
	 */
	public static function get_by_options_hash($options_hash) {
		$options = static::get_options_from_hash($options_hash);
		$pager = new static($options['classname']);
		$pager->options = $options;
		return $pager;
	}
}
