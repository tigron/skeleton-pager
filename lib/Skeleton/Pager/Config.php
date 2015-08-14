<?php
/**
 * Config class
 * Configuration for Skeleton\File\Picture
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Pager;

class Config {

	/**
	 * Items per page
	 *
	 * @access public
	 * @var int $items_per_page
	 */
	public static $items_per_page = 20;

	/**
	 * Sticky pager
	 *
	 * Remeber pager when navigating away and back to the module
	 *
	 * @access public
	 * @var bool $sticky_pager
	 */
	public static $sticky_pager = true;

}
