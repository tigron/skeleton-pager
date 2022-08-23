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
			if (!is_array($this->value)) {
				throw new \Exception('Error in condition: the IN value specified for ' . $this->local_field . ' must be an array');
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
				$condition .= $db->quote_identifier($this->local_field) . ' IN (' . $list . ')';
			}
			if (count($null) > 0) {
				$condition .= ' OR ' . $db->quote_identifier($this->local_field) . ' IS NULL )';
			}
			$condition .= ') ';

			return $condition;
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
