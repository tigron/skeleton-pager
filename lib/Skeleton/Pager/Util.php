<?php
/**
 * Util Class
 *
 * Some utils for general purpose
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Pager;

class Util {

	/**
	 * Object sort
	 *
	 * @access public
	 * @param array $objects
	 * @param string $property
	 * @param string $direction (asc/desc)
	 * @param string $type (string/date/int)
	 */
	public static function object_sort($objects, $property, $direction = 'asc', $type = 'auto') {
		usort($objects, function($a, $b) use ($property, $direction, $type) {
			if (!is_object($property) AND isset($a->$property)) {
				$property1 = $a->$property;
				$property2 = $b->$property;
			} elseif (is_callable(array($a, $property))) {
				$property1 = call_user_func_array( array($a, $property), array());
				$property2 = call_user_func_array( array($b, $property), array());
			} elseif (is_callable($property)) {
				$property1 = $property($a);
				$property2 = $property($b);
			}

			if (is_numeric($property1) AND is_numeric($property2) AND $type == 'auto') {
				$type = 'int';
			}

			if ($type == 'string') {
				$cmp = strcasecmp($property1, $property2);
			} elseif ($type == 'date') {
				if (strtotime($property1) > strtotime($property2)) {
					$cmp = 1;
				} else {
					$cmp = -1;
				}
			} else {
				$cmp = $property1 > $property2 ? 1 : -1;
			}

			if ($direction == 'desc') {
				return -1*$cmp;
			} else {
				return $cmp;
			}
		});
		return $objects;
	}
}
