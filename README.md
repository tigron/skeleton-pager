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
	$pager->add_condition('field2', 'IN', [ 1, 2, 3]);

	$condition = new \Skeleton\Pager\Sql\Condition('my_other_field', '>', '0);
	$pager->add_join('remote_table', remote_id', 'local_field', $condition);

	$pager->page();
