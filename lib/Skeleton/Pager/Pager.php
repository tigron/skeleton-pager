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
		'jump_to' => true,
		'joins' => [],
		'sort_permissions' => [],
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

		$this->classname = $classname;
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
	 * Set direction
	 *
	 * @access public
	 * @param string $direction
	 */
	public function set_direction($direction = 'asc') {
		$this->options['direction'] = $direction;
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

		if (is_a($params, '\Skeleton\Pager\Sql\Condition')) {
			$condition[$field][] = $params;
			return;
		}

		$field = array_shift($params);
		$field = $this->expand_field_name($field);

		if (count($params) == 1) {
			$condition = new Condition($field, '=', array_shift($params));
		} else {
			$condition = new Condition($field, array_shift($params), $params);
		}
		$conditions[$field][] = $condition;

		$this->options['conditions'] = $conditions;
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
		/*
			$extra_join = [
				$remote_table,
				$remote_id,
				$local_field
			]
		*/

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
	 * Activate 'Jump to page'
	 *
	 * @access public
	 * @param bool $jump_to
	 */
	public function set_jump_to($jump_to) {
		$this->options['jump_to'] = $jump_to;
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
	 * @param string $key
	 */
	public function clear_condition($key) {
		unset($this->options['conditions'][$key]);
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
	protected function get_options_from_hash($hash) {
		return unserialize(base64_decode(urldecode($hash)));
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
	public function create_options_hash($conditions = null, $page = null, $sort = null, $direction = null) {
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

		$options = array(
			'classname' => $this->classname,
			'conditions' => $conditions,
			'page' => $page,
			'sort' => $sort,
			'direction' => $direction,
			'joins' => $this->options['joins'],
		);

		$hash = urlencode(base64_encode(serialize($options)));
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

		$sort = $this->options['sort'];

		$this->options['all'] = $all;

		$params = [
			$sort,
			$this->options['direction'],
			$this->options['page'],
			$this->options['conditions'],
			$this->options['all'],
			$this->options['joins']
		];

		$this->items = call_user_func_array([$this->classname, 'get_paged'], $params);
		$this->item_count = call_user_func_array([$this->classname, 'count'], [$this->options['conditions'], $this->options['joins']]);
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
		$options = unserialize(base64_decode(urldecode($options_hash)));
		$pager = new self($options['classname']);
		$pager->options = $options;
		return $pager;
	}
}
