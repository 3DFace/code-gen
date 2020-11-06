<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class MixedType implements TypeDef
{

	public function getUses(string $namespace) : array
	{
		return [];
	}

	public function getSerializer(string $value_expression, bool $null_able, string $indent) : string
	{
		return $value_expression;
	}

	public function getDeserializer(string $l_value, string $indent) : string
	{
		return '';
	}

	public function getArgumentHint() : string
	{
		return '';
	}

	public function getPhpDocHint() : string
	{
		return 'mixed';
	}

}
