<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class ArrayType implements TypeDef
{

	private TypeDef $innerType;

	public function __construct(TypeDef $innerType)
	{
		$this->innerType = $innerType;
	}

	public function getUses($namespace) : array
	{
		return $this->innerType->getUses($namespace);
	}

	public function getSerializer(string $value_expression, bool $null_able, string $indent) : string
	{
		if (\is_a($this->innerType, ScalarType::class)) {
			return $value_expression;
		}
		$type_hint = $this->innerType->getArgumentHint();
		return ($null_able ? "$value_expression === null ? null : " : '')."\\array_map(static function ($type_hint \$x){\n".
			$indent."\t".'return '.$this->innerType->getSerializer('$x', false, $indent."\t").";\n".
			$indent."}, $value_expression)";
	}

	public function getDeserializer(string $l_value, string $indent) : string
	{
		return "if($l_value !== null){\n".
			$indent."\t$l_value = \\array_map(static function (\$x){\n".
			$indent."\t\t".$this->innerType->getDeserializer('$x', $indent."\t\t").
			$indent."\t\t".'return $x'.";\n".
			$indent."\t}, $l_value);\n".
			$indent."}\n";
	}

	public function getArgumentHint() : string
	{
		return 'array';
	}

	public function getPhpDocHint() : string
	{
		$inner = $this->innerType->getPhpDocHint();
		return \str_replace('|', '[]|', $inner).'[]';
	}

}
