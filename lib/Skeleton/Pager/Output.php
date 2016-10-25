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
	 * Constructor
	 *
	 * @access public
	 * @param \Skeleton\Pager\Pager $pager
	 */
	public function __construct(\Skeleton\Pager\Pager $pager) {
		$this->pager = $pager;
	}

}
