<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class ScalarType implements TypeDef
{

	private string $type;
	private bool $nullable;

	public function __construct(string $type, bool $nullable = false)
	{
		$this->type = $type;
		$this->nullable = $nullable;
	}

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
		return "$value_expression === null ? null : ($this->type)$value_expression";
	}

	public function getEqualizer(string $exp1, string $exp2, string $indent) : string
	{
		return "$exp1 === $exp2";
	}

	public function getArgumentHint() : string
	{
		return ($this->nullable ? '?' : '').$this->type;
	}

	public function getPhpDocHint() : string
	{
		return $this->type.($this->nullable ? '|null' : '');
	}

	public function createNullable() : TypeDef
	{
		$x = clone $this;
		$x->nullable = true;
		return $x;
	}

}
