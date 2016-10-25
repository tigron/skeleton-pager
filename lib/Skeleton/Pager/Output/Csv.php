<?php
/**
 * Output class
 * Abstract Output class for Pager
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Pager\Output;

class Csv extends \Skeleton\Pager\Output {

	/**
	 * Output
	 *
	 * @access public
	 * @return string $csv
	 */
	public function output() {
		$arguments = func_get_args();
		$this->pager->page();
		$result = [];

		if (count($arguments) == 1) {
			$headers = $arguments[0];
			$fields = $arguments[0];
		} elseif (count($arguments == 2)) {
			$headers = $arguments[0];
			$fields = $arguments[1];
			if (count($headers) != count($fields)) {
				throw new \Exception('If headers and fields are given, both should have an equal amount of values');
			}
		} else {
			throw new \Exception('This function requires 1 or 2 arrays as parameter: The first is the header, second are the field names or closures.');
		}


		foreach ($this->pager->items as $item) {
			$row = [];
			foreach ($headers as $key => $header) {
				if (is_callable($fields[$key])) {
					$row[$header] = $fields[$key]($item);
				} else {
					$row[$header] = \Skeleton\Pager\Util::object_get_attribute($item, $fields[$key]);
				}
			}
			$result[] = $row;
		}

		$this->output_array($headers, $result);

	}

	/**
	 * Output the array
	 *
	 * @access private
	 * @param array $headers
	 * @param array $values
	 */
	private function output_array($headers, $array) {
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . $this->pager->get_classname() . '.csv');

		foreach ($headers as $header) {
			echo $header . ';';
		}
		echo "\n";
		foreach ($array as $values) {
			foreach ($values as $value) {
				echo $value . ';';
			}
			echo "\n";
		}
		exit;

		$result = $this->pager->page();
		foreach ($fields as $field) {
			echo $field . ';';
		}
		echo "\n";
		foreach ($this->pager->items as $item) {
			foreach ($fields as $field) {
				echo \Skeleton\Pager\Util::object_get_attribute($item, $field) . ';';
			}
			echo "\n";
		}
		exit;
	}

}
