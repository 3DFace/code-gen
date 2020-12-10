<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

interface ClassWriter
{

	/**
	 * @param string $class_name
	 * @param string $php_code
	 * @return void
	 */
	public function writeClass(string $class_name, string $php_code) : void;

	public function getTargetMTime(string $class_name) : int;

}
