<?php
/**
 * Sql Join
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Pager\Sql;
use \Skeleton\Database\Database;

class Join {

	/**
	 * remote table
	 *
	 * @access private
	 * @var $remote_table
	 */
	private $remote_table = null;

	/**
	 * remote id
	 *
	 * @access private
	 * @var $remote_id
	 */
	private $remote_id = null;

	/**
	 * local_field
	 *
	 * @access private
	 * @var $local_field
	 */
	private $local_field = null;

	/**
	 * Conditions
	 *
	 * @access private
	 * @var array $conditions
	 */
	private $conditions = [];

	/**
	 * Construct
	 *
	 * @access public
	 * @param string $remote_table
	 * @param string $remote_id
	 * @param string $local_field
	 */
	public function __construct($remote_table, $remote_id, $local_field) {
		$this->remote_table = $remote_table;
		$this->remote_id = $remote_id;
		$this->local_field = $local_field;
	}

	/**
	 * Add condition
	 *
	 * @access public
	 * @param Condition $condition
	 */
	public function add_condition(Condition $condition) {
		$this->conditions[] = $condition;
	}

	/**
	 * Get remote_table
	 *
	 * @access public
	 * @return string $remote_table
	 */
	public function get_remote_table() {
		return $this->remote_table;
	}

	/**
	 * Get remote_id
	 *
	 * @access public
	 * @return string $remote_id
	 */
	public function get_remote_id() {
		return $this->remote_id;
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
	 * Get local_field
	 *
	 * @access public
	 * @return string $local_field
	 */
	public function get_conditions() {
		return $this->conditions;
	}

	/**
	 * tostring
	 *
	 * @access public
	 * @return string $condition
	 */
	public function __toString() {
		$db = Database::get();
		$join = 'LEFT OUTER JOIN ' . $db->quote_identifier($this->remote_table) . ' on (' . $db->quote_identifier($this->remote_table . '.' . $this->remote_id) . ' = ' . $db->quote_identifier($this->local_field);
		foreach ($this->conditions as $condition) {
			$join .= ' AND ' . $condition;
		}
		$join .= ')' . "\n";
		return $join;
	}

}
