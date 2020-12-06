<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class ArrayType implements TypeDef
{

	private TypeDef $innerType;
	private bool $nullable;

	public function __construct(TypeDef $innerType, bool $nullable)
	{
		$this->innerType = $innerType;
		$this->nullable = $nullable;
	}

	public function getUses($namespace) : array
	{
		return $this->innerType->getUses($namespace);
	}

	public function getSerializer(string $value_expression, string $indent) : string
	{
		if (\is_a($this->innerType, ScalarType::class)) {
			return $value_expression;
		}
		$type_hint = $this->innerType->getArgumentHint();
		return ($this->nullable ? "$value_expression === null ? null : " : '')."\\array_map(static function ($type_hint \$x){\n".
			$indent."\t".'return '.$this->innerType->getSerializer('$x', $indent."\t").";\n".
			$indent."}, $value_expression)";
	}

	public function getDeserializer(string $value_expression, string $indent) : string
	{
		return "$value_expression === null ? null : \\array_map(static function (\$x){\n".
			$indent."\t".'return '.$this->innerType->getDeserializer('$x', $indent."\t").";\n".
			$indent."}, $value_expression)";
	}

	public function getEqualizer(string $exp1, string $exp2, string $indent) : string
	{
		$not_null = "\count($exp1) === \count($exp2)\n$indent&& (static function(\$arr1, \$arr2){\n".
			$indent."\t"."foreach(\$arr1 as \$i => \$v1){\n".
			$indent."\t\t"."if(!isset(\$arr2[\$i])){\n".
			$indent."\t\t\t"."return false;\n".
			$indent."\t\t"."}\n".
			$indent."\t\t"."\$v2 = \$arr2[\$i];\n".
			$indent."\t\t"."\$v_eq = ".$this->innerType->getEqualizer('$v1', '$v2', $indent."\t\t\t").";\n".
			$indent."\t\t"."if(!\$v_eq){\n".
			$indent."\t\t\t"."return false;\n".
			$indent."\t\t"."}\n".
			$indent."\t"."}\n".
			$indent."\t"."return true;\n".
			$indent."})($exp1, $exp2)";
		if (!$this->nullable) {
			return $not_null;
		}
		return "(($exp1 === null && $exp2 === null)\n$indent|| ($exp1 !== null && $exp2 !== null\n$indent&& $not_null))";
	}

	public function getArgumentHint() : string
	{
		return ($this->nullable ? '?' : '').'array';
	}

	public function getPhpDocHint() : string
	{
		$inner = $this->innerType->getPhpDocHint();
		return \str_replace('|', '[]|', $inner).'[]'.($this->nullable ? '|null' : '');
	}

}
