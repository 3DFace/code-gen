<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class Psr0ClassWriter implements ClassWriter {

	/** @var string */
	private $targetSrcRoot;

	/**
	 * Psr0ClassWriter constructor.
	 * @param string $targetSrcRoot
	 */
	public function __construct($targetSrcRoot){
		$this->targetSrcRoot = $targetSrcRoot;
	}

	public function writeClass($className, $phpCode){
		$class_filename = $this->targetSrcRoot.'/'.$this->classNameToPsr0Name($className);
		$dir = \dirname($class_filename);
		if(!@\mkdir($dir, 0777, true) && !\is_dir($dir)){
			throw new \InvalidArgumentException("Can't create dir $dir");
		}
		if(\is_readable($class_filename)){
			$present = \file_get_contents($class_filename);
			if($present === $phpCode){
				return;
			}
		}
		\file_put_contents($class_filename, $phpCode);
	}

	private function classNameToPsr0Name($className){
		return \str_replace('\\', DIRECTORY_SEPARATOR, $className).'.php';
	}

}
