<?php
/**
 * Handles paginating of query results
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Pager\Web;

use \Skeleton\Pager\Config;
use \Skeleton\Pager\Sql\Condition;
use \Skeleton\Pager\Sql\Join;

class Pager extends \Skeleton\Pager\Pager {

	/**
	 * Link: a html string with pager links
	 *
	 * @access public
	 * @var string $link
	 */
	public $links;

	/**
	 * Per page link: a html string with per page links
	 *
	 * @access public
	 * @var string $per_page_links
	 */
	public $per_page_links;

	/**
	 * Create the header cells of the paged table
	 *
	 * @param string $header Name of the header
	 * @param string $field_name Name of the database field that is represented here
	 * @return string $output
	 * @access public
	 */
	public function create_header($header, $field_name) {
		$object = new \ReflectionClass($this->classname);
		if (is_callable($field_name) === false AND $object->hasMethod($field_name) === false) {
			$field_name = $this->expand_field_name($field_name);
		}

		if ($this->options['sort'] == $field_name) {
			if ($this->options['direction'] == 'asc') {
				$direction = 'desc';
			} else {
				$direction = 'asc';
			}
		} else {
			$direction = 'asc';
		}

		$hash = $this->create_options_hash($this->options['conditions'], $this->options['page'], $field_name, $direction, $this->options['joins']);

		parse_str($_SERVER['QUERY_STRING'], $qry_str_parts);
		$qry_str_parts['q'] = $hash;

		$url = self::find_page_uri() . '?' . http_build_query($qry_str_parts);

		$template = \Skeleton\Application\Web\Template::get();
		$template->assign('url', $url);
		$template->assign('options', $this->options);
		$template->assign('field_name', $field_name);
		$template->assign('header', $header);
		$template->assign('pager', $this);
		return $template->render(\Skeleton\Pager\Config::$header_template, false);
	}

	/**
	 * Paginate the results
	 *
	 * @access private
	 */
	public function page($all = false) {
		$pager_uri_key = $this->get_pager_uri_key();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			if (!isset($_GET['q']) AND isset($_SESSION['pager'][$pager_uri_key]) AND Config::$sticky_pager) {
				$this->options = array_replace_recursive($this->options, $this->get_options_from_hash($_SESSION['pager'][$pager_uri_key]));
			} elseif (isset($_GET['q'])) {
				$query_options = $this->get_options_from_hash($_GET['q']);

				// If multiple pagers exist on the same page, only load the options of the one with the same classname
				if ($query_options['classname'] == $this->classname) {
					unset($this->options['conditions']);
	 				$this->options = array_replace_recursive($this->options, $this->get_options_from_hash($_GET['q']));
				}
			}
		}

		if (isset($_GET['p'])) {
			$this->set_page($_GET['p']);
		}

		parent::page($all);
		$this->generate_links();
		$this->generate_per_page_links();
		$hash = $this->create_options_hash($this->options['conditions'], $this->options['page'], $this->options['sort'], $this->options['direction'], $this->options['joins']);

		if (Config::$sticky_pager) {
			$_SESSION['pager'][$pager_uri_key] = $hash;
		}
	}

	/**
	 * Generate the necessary links to navigate the paged result
	 *
	 * @access private
	 */
	protected function generate_links() {
		$items_per_page = $this->options['per_page'];
		if ($items_per_page == 0) {
			$pages = 0;
		} else {
			$pages = ceil($this->item_count / $items_per_page);
		}

		// Don't make links if there is only one page
		if ($pages == 1) {
			$this->links = '';
			return;
		}

		$links = [];

		$links[] = [
			'page' => 'previous'
		];

		for ($i = 1; $i <= $pages; $i++) {
			$print = false;

			// Display the first two pages
			if ($i < 2) {
				$print = true;
			}

			// Display the two pages before and after the current one
			if ($i >= $this->options['page']-2 AND $i <= $this->options['page']+2) {
				$print = true;
			}

			// Make sure at least 9 pages are printed all the time
			if (($this->options['page'] < 5 AND $i <= 7) OR ($this->options['page'] > $pages-5 AND $i >= $pages-6)) {
				$print = true;
			}

			// Display the last two pages
			if ($i > $pages-1) {
				$print = true;
			}

			if ($print === true) {
				$links[] = [
					'page' => $i
				];
				$previous_print = $i;
			}
		}

		$links[] = [
			'page' => 'next'
		];

		foreach ($links as $key => $link) {
			$link['active'] = false;
			$links[$key] = $link;
		}

		$qry_str = '';
		if (isset($_SERVER['QUERY_STRING'])) {
			$qry_str = $_SERVER['QUERY_STRING'];
		}
		parse_str($qry_str, $qry_str_parts);


		foreach ($links as $key => $link) {

			if ($link['page'] === 'previous') {
				$number = $this->options['page']-1;
			} elseif ($link['page'] === 'next') {
				$number = $this->options['page']+1;
			} elseif ($link['page'] == $this->options['page']) {
				$number = $link['page'];
				$link['active'] = true;
				$links[$key] = $link;
			} else {
				$number = $link['page'];
			}

			$hash = $this->create_options_hash($this->options['conditions'], $number, $this->options['sort'], $this->options['direction'], $this->options['joins']);
			$link['hash'] = $hash;

			$qry_str_parts['q'] = $hash;
			if (isset($qry_str_parts['p'])) {
				unset($qry_str_parts['p']);
			}

			$url = self::find_page_uri() . '?' . http_build_query($qry_str_parts);
			$link['url'] = $url;
			$links[$key] = $link;
		}

		$template = \Skeleton\Application\Web\Template::get();
		$template->assign('links', $links);
		$template->assign('classname', $this->classname);
		$template->assign('options', $this->options);
		$output = $template->render(\Skeleton\Pager\Config::$links_template, false);
		$this->links = $output;
	}

	/**
	 * Generate the necessary per page links to change the amount of items per page
	 *
	 * @access private
	 */
	protected function generate_per_page_links() {
		$items_per_page = $this->options['per_page'];
		if ($items_per_page == 0) {
			$pages = 0;
		} else {
			$pages = ceil($this->item_count / $items_per_page);
		}

		// Don't make links if there is only one page and the items per page is 20
		if ($pages == 1 && $items_per_page === 20) {
			$this->per_page_links = '';
			return;
		}

		$qry_str = '';
		if (isset($_SERVER['QUERY_STRING'])) {
			$qry_str = $_SERVER['QUERY_STRING'];
		}
		parse_str($qry_str, $qry_str_parts);

		// list of items per page
		$per_page_links = [];
		foreach (Config::$per_page_list as $per_page) {
			$hash = $this->create_options_hash(
				$this->options['conditions'],
				1,
				$this->options['sort'],
				$this->options['direction'],
				$this->options['joins'],
				$per_page
			);

			$qry_str_parts['q'] = $hash;
			if (isset($qry_str_parts['per_page']) === true) {
				unset($qry_str_parts['per_page']);
			}

			$url = self::find_page_uri() . '?' . http_build_query($qry_str_parts);
			$per_page_links[] = [
				'per_page' => $per_page,
				'url' => $url,
				'hash' => $hash
			];
		}

		$template = \Skeleton\Application\Web\Template::get();
		$template->assign('per_page_links', $per_page_links);
		$template->assign('classname', $this->classname);
		$template->assign('options', $this->options);
		$output = $template->render(\Skeleton\Pager\Config::$per_page_template, false);
		$this->per_page_links = $output;
	}

	/**
	 * Clear conditions
	 *
	 * @access public
	 */
	public function clear_conditions() {
		parent::clear_conditions();

		if (Config::$sticky_pager) {
			$pager_uri_key = $this->get_pager_uri_key();
			unset($_SESSION['pager'][$pager_uri_key]);
		}
	}

	/**
	 * Clear sticky pager data
	 *
	 * @access public
	 */
	public static function clear_sticky() {
		if (Config::$sticky_pager === false) {
			return;
		}

		if (isset($_SESSION['pager'])) {
			unset($_SESSION['pager']);
		}
	}

	/**
	 * Get key for storing the options hash in session
	 *
	 * @access private
	 * @return string $pager_uri_key
	 */
	protected function get_pager_uri_key() {
		$request_uri = str_replace('?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
		$qry_str = $_SERVER['QUERY_STRING'];


		parse_str($qry_str, $qry_str_parts);
		unset($qry_str_parts['p']);
		unset($qry_str_parts['q']);
		$pager_uri_key = base64_encode(strtolower($this->classname) . '/' . str_replace('/index', '', $request_uri) . '?' . implode('&', $qry_str_parts));

		return $pager_uri_key;
	}

	/**
	 * Find out how we should refer to the current page
	 *
	 * @access private
	 * @return string $uri
	 */
	private static function find_page_uri() {
		// We need to remove the base_uri from the link, because it will get
		// rewritten afterwards. If we leave it, it will be prepended again,
		// which makes the link invalid.
		if (class_exists('\Skeleton\Core\Application')) {
			$application = \Skeleton\Core\Application::get();
		}

		$request_uri = str_replace('?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
		if (
			isset($application->config->base_uri) AND
			$application->config->base_uri !== '/' AND
			strpos($request_uri, $application->config->base_uri) === 0
		) {
			$url = '/' . substr($request_uri, strlen($application->config->base_uri));
		} else {
			$url = $request_uri;
		}

		return $url;
	}
}
