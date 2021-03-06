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
	 */
	public function __construct($local_field, $comparison, $value) {
		$this->local_field = $local_field;
		$this->comparison = $comparison;
		if (!is_array($value)) {
			$this->value = [ $value ];
		} else {
			$this->value = $value;
		}
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
		if ($this->comparison == 'IN') {
			if (is_array($this->value)) {
				$list = implode($db->quote($this->value), ', ');
			} else {
				$list = $this->value;
			}

			if (strlen($list) == 0) {
				return '1 = 2' . "\n\t";
			} else {
				return $db->quote_identifier($this->local_field) . ' IN (' . $list . ')' . "\n\t";
			}
		} elseif ($this->comparison == 'BETWEEN') {
			return $db->quote_identifier($this->local_field) . ' BETWEEN ' . $db->quote($this->value[0]) . ' AND ' . $db->quote($this->value[1]) . "\n\t";
		} elseif (is_array($this->value)) {
			$where = '(0';
			foreach ($this->value as $field) {
				$where .= ' OR ' . $db->quote_identifier($this->local_field) . ' ' . $this->comparison . ' ' . $db->quote($field);
			}
			$where .= ') ';
			return $where;
		} else {
			return $db->quote_identifier($this->local_field) . ' ' . $this->comparison . ' ' . $db->quote($this->value) . ' ' . "\n\t";
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
