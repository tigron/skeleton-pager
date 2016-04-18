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
		$this->value = $value;
	}

	/**
	 * tostring
	 *
	 * @access public
	 * @return string $condition
	 */
	public function __toString() {
		try {
		$db = Database::get();
		if ($this->comparison == 'IN') {
			if (is_array($this->value)) {
				$list = implode($this->value, ', ');
			} else {
				$list = $this->value;
			}

			return $db->quote_identifier($this->local_field) . ' IN (' . $list . ')' . "\n\t";
		} elseif (is_array($this->value)) {
			$where = '(0';
			foreach ($this->value as $field) {
				$where .= ' OR ' . $db->quote_identifier($this->local_field) . ' ' . $this->comparison . ' ' . $db->quote($field);
			}
			$where .= ') ';
			return $where;
		} elseif ($this->comparison == 'BETWEEN') {
			return $db->quote_identifier($this->local_field) . ' BETWEEN ' . $db->quote($this->value[0]) . ' AND ' . $db->quote($this->value[1]) . "\n\t";
		} else {
			return $db->quote_identifier($this->local_field) . ' ' . $this->comparison . ' ' . $db->quote($this->value) . ' ' . "\n\t";
		}
		} catch (\Exception $e) {
			print_r($e);
			die();
		}
	}


}
