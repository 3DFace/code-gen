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

	public static function plainVarExport($var, $indent = '') : string
	{
		if ($var === null) {
			return 'null';
		}
		if ($var === []) {
			return '[]';
		}

		if (\is_scalar($var)) {
			return \var_export($var, true);
		}

		if (\is_array($var)) {
			$pairs = [];
			$values = [];
			$i = 0;
			$is_map = false;
			$sub_indent = $indent."\t";
			foreach ($var as $k => $v) {
				$is_map = $is_map || $k !== $i;
				$k_exp = self::plainVarExport($k, $sub_indent);
				$v_exp = self::plainVarExport($v, $sub_indent);
				$pairs[] = $k_exp.' => '.$v_exp;
				$values[] = $v_exp;
				$i++;
			}
			return "[\n$sub_indent".\implode(",\n$sub_indent", $is_map ? $pairs : $values).",\n$indent]";
		}
		throw new \InvalidArgumentException('Can not export value of type '.\gettype($var));
	}

}
