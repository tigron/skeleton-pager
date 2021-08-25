<?php
/**
 * Output class
 * Abstract Output class for Pager
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Pager;

abstract class Output {

	/**
	 * Pager
	 *
	 * @var \Skeleton\Pager\Pager $pager
	 * @access protected
	 */
	protected $pager;

	/**
	 * Filename
	 *
	 * @var String
	 * @access protected
	 */
	protected $filename;

	/**
	 * Constructor
	 *
	 * @access public
	 * @param \Skeleton\Pager\Pager $pager
	 */
	public function __construct(\Skeleton\Pager\Pager $pager, $filename = null) {
		$this->pager = $pager;
		if ($filename == null) {
			$this->filename = $pager->get_classname();
		} else {
			$this->filename = $filename;
		}
	}

	/**
	 * Output
	 *
	 * @access public
	 * @return
	 */
	public function output() {
		$arguments = func_get_args();
		$file = call_user_func_array([ $this, 'get_file'], $arguments);
		$file->client_download();
	}

	/**
	 * Save
	 *
	 * @access public
	 * @return File
	 */
	public function save() {
		$arguments = func_get_args();
		$file = call_user_func_array([ $this, 'get_file'], $arguments);
		return $file;
	}
}
