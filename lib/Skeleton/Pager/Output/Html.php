<?php
/**
 * Html class
 *
 * @author Lionel Laffineur <lionel@tigron.be>
 */

namespace Skeleton\Pager\Output;

class Html extends \Skeleton\Pager\Output {

	/**
	 * Output
	 *
	 * @access public
	 * @return string $html
	 */
	public function output() {
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

		echo("<table class='Skeleton-Pager-Output-Html-" . $this->pager->get_classname() . "'>\n");
		echo("	<thead>\n");
		echo("		<tr>\n");
		foreach ($headers as $header) {
			echo("			<th class='" . $this->clean($header) . "'>" . $header . "</th>\n");
		}
		echo("		</tr>\n");
		echo("	</thead>\n");

		echo("	<tbody>\n");
		foreach ($this->pager->items as $item) {
			echo("		<tr>\n");
			foreach ($headers as $key => $header) {
				if (is_callable($fields[$key])) {
					$value = $fields[$key]($item);
				} else {
					$value = \Skeleton\Pager\Util::object_get_attribute($item, $fields[$key]);
				}
				echo("			<td class='" . $this->clean($header) . "'>" . $value . "</td>\n");
			}
			echo("		</tr>\n");
		}
		echo("	</tbody>\n");
		echo("</table>\n");
	}

	/**
	 * Save
	 *
	 * @access public
	 * @return File
	 */
	public function save() {
		$arguments = func_get_args();
		ob_start();
		call_user_func_array([ $this, 'output' ], $arguments);
		$content = ob_get_clean();
		return \Skeleton\File\File::store($this->filename . '.html', $content);
	}

	/**
	 * clean
	 * clean the given string so it can be used as a class name on an element
	 *
	 * @access private
	 * @param string
	 * @return string
	 */
	private function clean($string) {
	   $string = str_replace(' ', '', $string);
	   $string = str_replace('-', '', $string);
	   return preg_replace('/[^A-Za-z0-9\-]/', '', $string);
	}
}
