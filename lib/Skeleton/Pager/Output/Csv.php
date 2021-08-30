<?php
/**
 * Csv class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Pager\Output;

class Csv extends \Skeleton\Pager\Output {

	/**
	 * Get file
	 *
	 * @access public
	 * @return File $file
	 */
	public function get_file() {
		$arguments = func_get_args();
		if ($this->pager->item_count == 0) {
			$this->pager->page();
		}
		$result = [];

		if (count($arguments) == 1) {
			$headers = $arguments[0];
			$fields = $arguments[0];
		} elseif (count($arguments) == 2) {
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

		$content = $this->output_array($headers, $result);

		$file = \Skeleton\File\File::store($this->filename . '.csv', $content);
		return $file;
	}

	/**
	 * Output the array
	 *
	 * @access private
	 * @param array $headers
	 * @param array $values
	 */
	private function output_array($headers, $array) {
		$content = '';

		foreach ($headers as $header) {
			$content .= $header . ';';
		}
		$content .= "\n";
		foreach ($array as $values) {
			foreach ($values as $value) {
				$content .= $value . ';';
			}
			$content .= "\n";
		}
		return $content;
	}

}
