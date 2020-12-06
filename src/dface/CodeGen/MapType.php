<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class MapType implements TypeDef
{

	private TypeDef $innerType;
	private bool $nullable;

	public function __construct(TypeDef $innerType, bool $nullable)
	{
		$this->innerType = $innerType;
		$this->nullable = $nullable;
	}
	public function getUses(string $namespace) : array
	{
		return $this->innerType->getUses($namespace);
	}

	public function getSerializer(string $value_expression, string $indent) : string
	{
		if (\is_a($this->innerType, ScalarType::class)) {
			return $value_expression;
		}
		$inner_hint = $this->innerType->getPhpDocHint();
		return ($this->nullable ? "$value_expression === null ? null : " : '')."(static function (array \$map){\n".
			$indent."\t"."\$x = [];\n".
			$indent."\t"."foreach(\$map as \$k => \$v){\n".
			$indent."\t\t/** @var $inner_hint \$v */\n".
			$indent."\t\t".'$x[$k] = '.$this->innerType->getSerializer('$v', $indent."\t\t").";\n".
			$indent."\t"."}\n".
			$indent."\t"."return \$x ?: new \stdClass();\n".
			$indent."})($value_expression)";
	}

	public function getDeserializer(string $value_expression, string $indent) : string
	{
		return "$value_expression === null ? null : "."(static function (\$map){\n".
			$indent."\t"."\$x = [];\n".
			$indent."\t"."foreach(\$map as \$k => \$v){\n".
			$indent."\t\t\$x[\$k] = ".$this->innerType->getDeserializer('$v', $indent."\t\t").";\n".
			$indent."\t"."}\n".
			$indent."\t"."return \$x;\n".
			$indent."})($value_expression)";
	}

	public function getEqualizer(string $exp1, string $exp2, string $indent) : string
	{
		$not_null = "\count($exp1) === \count($exp2)\n$indent&& (static function(\$map1, \$map2){\n".
			$indent."\t"."foreach(\$map1 as \$k => \$v1){\n".
			$indent."\t\t"."if(!isset(\$map2[\$k])){\n".
			$indent."\t\t\t"."return false;\n".
			$indent."\t\t"."}\n".
			$indent."\t\t"."\$v2 = \$map2[\$k];\n".
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
		return "(($exp1 === null && $exp2 === null) || ($exp1 !== null && $exp2 !== null && $not_null))";
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
