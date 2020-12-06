<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class MixedType implements TypeDef
{

	public function getUses(string $namespace) : array
	{
		return [];
	}

	public function getSerializer(string $value_expression, string $indent) : string
	{
		return $value_expression;
	}

	public function getDeserializer(string $value_expression, string $indent) : string
	{
		return $value_expression;
	}

	public function getEqualizer(string $exp1, string $exp2, string $indent) : string
	{
		return "$exp1 === $exp2";
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
