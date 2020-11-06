<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class ScalarType implements TypeDef
{

	private string $type;

	public function __construct(string $type)
	{
		$this->type = $type;
	}

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
		return "if($l_value !== null){\n".
			$indent."\t"."$l_value = ($this->type)$l_value;\n".
			$indent."}\n";
	}

	public function getArgumentHint() : string
	{
		return $this->type;
	}

	public function getPhpDocHint() : string
	{
		return $this->type;
	}

}
