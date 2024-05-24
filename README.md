# skeleton-pager

## Description

This library enables paging functionality for objects created with traits in
skeleton-object.


## Installation

Installation via composer:

    composer require tigron/skeleton-pager

## Howto

    $pager = new Pager('my_object');

	$pager->add_sort_permission('field1');
	$pager->add_sort_permission('field2');
	$pager->add_sort_permission('field3');
	$pager->add_sort_permission('remote_table.field4');
	$pager->set_sort('field3');
	$pager->set_direction('desc');

	if (isset($_POST['search'])) {
		$pager->set_search($_POST['search'], [ 'field2', 'remote_table.field4');
	}

	$pager->add_condition('field1', '=', 1);
	$pager->add_condition('field2', 'IN', [ 1, 2, 3, null]);

	$condition = new \Skeleton\Pager\Sql\Condition('my_other_field', '>', '0);
	$pager->add_join('remote_table', remote_id', 'local_field', $condition);

	$pager->page();

## Configuration

	/**
	 * Items per page
	 */
	\Skeleton\Pager\Config::$items_per_page = 20;

	/**
	 * Per page list
	 */
	\Skeleton\Pager\Config::$per_page_list = [20, 50, 100];

	/**
	 * Sticky pager
	 *
	 * Remember pager when navigating away and back to the module
	 */
	\Skeleton\Pager\Config::$sticky_pager = false;

	/**
	 * Links template
	 *
	 * Set the template to render the page links
	 */
	\Skeleton\Pager\Config::$links_template = '@skeleton-pager\links.twig';
