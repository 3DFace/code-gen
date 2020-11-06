<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

interface ClassWriter
{

	/**
	 * @param string $className
	 * @param string $phpCode
	 * @return void
	 */
	public function writeClass(string $className, string $phpCode) : void;

	public function getTargetMTime(string $className) : int;

}
