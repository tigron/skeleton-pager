<?php
/**
 * Sql Condition
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Pager\Sql;
use \Skeleton\Database\Database;

class Condition {

	/**
	 * Local field
	 *
	 * @access private
	 * @var $local_field
	 */
	private $local_field = '';

	/**
	 * Local function
	 *
	 * @access private
	 * @var $local_function
	 */
	private $local_function = '';

	/**
	 * Vakue
	 *
	 * @access private
	 * @var $value
	 */
	private $value = '';

	/**
	 * comparison
	 *
	 * @access private
	 * @var $comparison
	 */
	private $comparison = "=";

	/**
	 * Construct
	 *
	 * @access public
	 * @param string $local_field
	 * @param string $comparison
	 * @param string $value
	 * @param string $local_function
	 */
	public function __construct($local_field, $comparison, $value, $local_function = null) {
		$this->local_field = $local_field;
		if ($this->validate() === false) {
			throw new \Exception("Unsupported MySQL function: '" . $this->local_field . "'");
		}

		$this->comparison = $comparison;
		if (!is_array($value)) {
			$this->value = [ $value ];
		} else {
			$this->value = $value;
		}
		if ($local_function !== null) {
			$this->local_function = $local_function;
		}
	}

	/**
	 * Validate
	 *
	 * @access public
	 * @return bool
	 */
	public function validate() {
		$pattern = "/^(TIME|DATE|MAX|MIN|AVG|SUM|WEEKDAY)\(([^)]+)\)$|^[^\s()]+$/";
		if (preg_match($pattern, $this->local_field, $matches)) {
			if (isset($matches[1]) === true) {
				$this->set_local_function($matches[1]);
				$this->set_local_field($matches[2]);
			}

			return true;
		}

		return false;
	}

	/**
	 * Set local_function
	 *
	 * @access public
	 * @param string $local_function
	 */
	public function set_local_function($local_function) {
		$this->local_function = $local_function;
	}

	/**
	 * Get local_function
	 */
	public function get_local_function() {
		return $this->local_function;
	}

	/**
	 * Set local_field
	 *
	 * @access public
	 * @return string $local_field
	 */
	public function set_local_field($local_field) {
		$this->local_field = $local_field;
	}

	/**
	 * Get local_field
	 *
	 * @access public
	 * @return string $local_field
	 */
	public function get_local_field() {
		return $this->local_field;
	}

	/**
	 * Get value
	 *
	 * @access public
	 * @return string $value
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * Get comparison
	 *
	 * @access public
	 * @return string $comparison
	 */
	public function get_comparison() {
		return $this->comparison;
	}


	/**
	 * tostring
	 *
	 * @access public
	 * @return string $condition
	 */
	public function __toString() {
		$db = Database::get();
		$local_field = $db->quote_identifier($this->local_field);
		if (empty($this->local_function) === false) {
			$local_field = $this->local_function . '(' . $local_field . ')';
		}

		if ($this->comparison == 'IN' || $this->comparison == 'NOT IN') {
			$not = '';
			if ($this->comparison == 'NOT IN') {
				$not = 'NOT ';
			}
			if (!is_array($this->value)) {
				throw new \Exception('Error in condition: the ' . $not . 'IN value specified for ' . $this->local_field . ' must be an array');
			}

			if (empty($this->value) === true) {
				return $local_field . ' ' . $not . 'IN (NULL)';
			}

			/**
			 * If there is a NULL value, it cannot be passed to Mysql in the
			 * same IN statement. A seperate comparison needs to be added
			 */
			$non_null = [];
			$null = [];

			foreach ($this->value as $value) {
				if ($value === null) {
					$null[] = $value;
				} else {
					$non_null[] = $value;
				}
			}

			$condition = '( ';
			if (count($non_null) > 0 ) {
				$list = implode(', ', $db->quote($non_null));
				$condition .= $local_field . ' ' . $not . 'IN (' . $list . ')';
			}
			if (count($null) > 0) {
				$condition .= ' OR ' . $local_field . ' IS ' . $not . 'NULL )';
			}
			$condition .= ') ';

			return $condition;
		} elseif ($this->comparison == 'BETWEEN') {
			return $local_field . ' BETWEEN ' . $db->quote($this->value[0]) . ' AND ' . $db->quote($this->value[1]) . "\n\t";
		} elseif (is_array($this->value)) {
			$where = '(0';
			foreach ($this->value as $field) {
				$where .= ' OR ' . $local_field . ' ' . $this->comparison . ' ' . $db->quote($field);
			}
			$where .= ') ';
			return $where;
		} else {
			return $local_field . ' ' . $this->comparison . ' ' . $db->quote($this->value) . ' ' . "\n\t";
		}
	}

	/**
	 * Equals
	 *
	 * @access public
	 * @param \Skeleton\Pager\Sql\Condition $condition
	 * @return boolean $equals
	 */
	public function equals(\Skeleton\Pager\Sql\Condition $condition) {
		if ($this->get_local_field() != $condition->get_local_field()) {
			return false;
		}

		if ($this->get_comparison() == '=' AND $condition->get_comparison() == 'IN') {
			foreach ($condition->get_value() as $value) {
				if (in_array($value, $this->get_value())) {
					return true;
				}
			}
		}

		if ($condition->get_comparison() == '=' AND $this->get_comparison() == 'IN') {
			foreach ($this->get_value() as $value) {
				if (in_array($value, $condition->get_value())) {
					return true;
				}
			}
		}

		if ($this->get_comparison() != $condition->get_comparison()) {
			return false;
		}

		if (!is_array($this->get_value()) and !is_array($condition->get_value())) {
			if ($this->get_value() != $condition->get_value()) {
				return false;
			}
		} elseif (is_array($this->get_value()) and is_array($condition->get_value())) {
			$diff = array_diff($this->get_value(), $condition->get_value());
			if (count($diff) > 0) {
				return false;
			}
		} else {
			return false;
		}

		return true;
	}

	/**
	 * evaluate the condition against the given value
	 *
	 * @access public
	 * @param $value
	 * @return bool
	 */
	public function evaluate($value) {
		switch ($this->comparison) {
			case '':
			case '=':
				foreach ($this->value as $value_item) {
					if ($value_item == $value) {
						return true;
					}
				}
				return false;
			case '!=':
			case '<>':
				foreach ($this->value as $value_item) {
					if ($value_item != $value) {
						return true;
					}
				}
				return false;
			case '<':
				foreach ($this->value as $value_item) {
					if ($value_item < $value) {
						return true;
					}
				}
				return false;
			case '>':
				foreach ($this->value as $value_item) {
					if ($value_item > $value) {
						return true;
					}
				}
				return false;
			case '<=':
				foreach ($this->value as $value_item) {
					if ($value_item <= $value) {
						return true;
					}
				}
				return false;
			case '>=':
				foreach ($this->value as $value_item) {
					if ($value_item >= $value) {
						return true;
					}
				}
				return false;
			default:
				throw new \Exception("Unsupported comparison operator: '" . $this->comparison . "'");
		}
	}
}
