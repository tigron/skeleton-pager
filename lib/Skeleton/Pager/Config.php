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
	 * Remember pager when navigating away and back to the module
	 *
	 * @access public
	 * @var bool $sticky_pager
	 */
	public static $sticky_pager = false;

	/**
	 * Links template
	 *
	 * Set the template to render the page links
	 *
	 * @access public
	 * @var string $links_template
	 */
	public static $links_template = '@skeleton-pager\bootstrap3\links.twig';

	/**
	 * header template
	 *
	 * Set the template to render the page header
	 *
	 * @access public
	 * @var string $header_template
	 */
	public static $header_template = '@skeleton-pager\bootstrap3\header.twig';

}
