<?php

namespace dface\CodeGen;

class Psr0ClassWriter implements ClassWriter
{

	private string $target_src_root;

	public function __construct(string $target_src_root)
	{
		$this->target_src_root = $target_src_root;
	}

	public function writeClass(string $class_name, string $php_code) : void
	{
		$class_filename = $this->target_src_root.'/'.self::classNameToPsr0Name($class_name);
		$dir = \dirname($class_filename);
		if (!@\mkdir($dir, 0777, true) && !\is_dir($dir)) {
			throw new \InvalidArgumentException("Can't create dir $dir");
		}
		if (\is_readable($class_filename)) {
			$present = \file_get_contents($class_filename);
			if ($present === $php_code) {
				\touch($class_filename);
				return;
			}
		}
		\file_put_contents($class_filename, $php_code);
	}

	public function getTargetMTime(string $class_name) : int
	{
		$class_filename = $this->target_src_root.'/'.self::classNameToPsr0Name($class_name);
		if (\is_readable($class_filename)) {
			return \filemtime($class_filename);
		}
		return 0;
	}

	private static function classNameToPsr0Name(string $class_name) : string
	{
		return \str_replace('\\', DIRECTORY_SEPARATOR, $class_name).'.php';
	}

}
