<?php

namespace dface\CodeGen;

class MixedType implements TypeDef
{

	public function getUses() : array
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

	public function createNullable() : TypeDef
	{
		return $this;
	}

	public function varExport($value, string $indent) : string
	{
		return Utils::plainVarExport($value, $indent);
	}

	public function isDefaultInlineable($value) : bool
	{
		return \is_scalar($value) || $value === null || $value === [];
	}

	public function serialize($value)
	{
		return $value;
	}

}
