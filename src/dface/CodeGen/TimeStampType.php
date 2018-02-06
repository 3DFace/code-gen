<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class TimeStampType implements TypeDef
{

	function getUses($namespace)
	{
		return [\DateTimeImmutable::class];
	}

	function getSerializer($value_expression, $indent)
	{
		return $value_expression.' !==null ? '.$value_expression.'->getTimestamp() : null';
	}

	function getDeserializer($target, $value_expression, $indent)
	{
		return "$target = $value_expression !== null ? (new DateTimeImmutable())->setTimestamp($value_expression) : null;\n";
	}

	function getArgumentHint()
	{
		return 'DateTimeImmutable';
	}

	function getPhpDocHint()
	{
		return 'DateTimeImmutable';
	}

}
