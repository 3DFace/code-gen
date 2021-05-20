<?php

namespace dface\CodeGen;

class Utils
{

	public static function camelCase(string $property_name) : string
	{
		$camelCase = \preg_replace_callback('/_([a-z])/', static function ($m) {
			return \strtoupper($m[1]);
		}, $property_name);
		return \strtoupper($camelCase[0]).\substr($camelCase, 1);
	}

	public static function fullTypeName(string $namespace, string $type_name) : string
	{
		return \strpos($type_name, '\\') === false ? $namespace.'\\'.$type_name : $type_name;
	}

	public static function varExport($var, $indent = '') : string
	{
		if ($var === null) {
			return 'null';
		}
		if ($var === []) {
			return '[]';
		}
		$exported = \var_export($var, true);
		if (\is_scalar($var)) {
			return $exported;
		}
		return \str_replace("\n", "\n".$indent, $exported);
	}

}
