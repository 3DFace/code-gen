<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class Psr0ClassWriter implements ClassWriter {

	/** @var string */
	private $targetSrcRoot;

	public function __construct($targetSrcRoot){
		$this->targetSrcRoot = $targetSrcRoot;
	}

	function writeClass(string $className, string $phpCode){
		$class_filename = $this->targetSrcRoot.'/'.$this->classNameToPsr0Name($className);
		@mkdir(dirname($class_filename), 0777, true);
		file_put_contents($class_filename, $phpCode);
	}

	private function classNameToPsr0Name($className){
		return str_replace('\\', DIRECTORY_SEPARATOR, $className).'.php';
	}

}
