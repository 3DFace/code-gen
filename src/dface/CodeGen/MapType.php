<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class MapType implements TypeDef
{

	private TypeDef $inner_type;
	private bool $nullable;

	public function __construct(TypeDef $innerType, bool $nullable = false)
	{
		$this->inner_type = $innerType;
		$this->nullable = $nullable;
	}

	public function getUses() : iterable
	{
		return $this->inner_type->getUses();
	}

	public function getSerializer(string $value_expression, string $indent) : string
	{
		if (\is_a($this->inner_type, ScalarType::class)) {
			return $value_expression;
		}
		$inner_hint = $this->inner_type->getPhpDocHint();
		return ($this->nullable ? "$value_expression === null ? null : " : '')."(static function (array \$map) {\n".
			$indent."\t"."\$x = [];\n".
			$indent."\t"."foreach (\$map as \$k => \$v) {\n".
			$indent."\t\t/** @var $inner_hint \$v */\n".
			$indent."\t\t".'$x[$k] = '.$this->inner_type->getSerializer('$v', $indent."\t\t").";\n".
			$indent."\t"."}\n".
			$indent."\t"."return \$x ?: new \stdClass();\n".
			$indent."})($value_expression)";
	}

	public function getDeserializer(string $value_expression, string $indent) : string
	{
		return "$value_expression === null ? null : "."(static function (\$map) {\n".
			$indent."\t"."\$x = [];\n".
			$indent."\t"."foreach (\$map as \$k => \$v) {\n".
			$indent."\t\t\$x[\$k] = ".$this->inner_type->getDeserializer('$v', $indent."\t\t").";\n".
			$indent."\t"."}\n".
			$indent."\t"."return \$x;\n".
			$indent."})($value_expression)";
	}

	private function notNullEq(string $exp1, string $exp2, string $indent) : string
	{
		return "\count($exp1) === \count($exp2)\n$indent&& (static function (\$map1, \$map2) {\n".
			$indent."\t"."foreach (\$map1 as \$k => \$v1) {\n".
			$indent."\t\t"."if (!isset(\$map2[\$k])) {\n".
			$indent."\t\t\t"."return false;\n".
			$indent."\t\t"."}\n".
			$indent."\t\t"."\$v2 = \$map2[\$k];\n".
			$indent."\t\t"."\$v_eq = ".$this->inner_type->getEqualizer('$v1', '$v2', $indent."\t\t").";\n".
			$indent."\t\t"."if (!\$v_eq) {\n".
			$indent."\t\t\t"."return false;\n".
			$indent."\t\t"."}\n".
			$indent."\t"."}\n".
			$indent."\t"."return true;\n".
			$indent."})($exp1, $exp2)";
	}

	public function getEqualizer(string $exp1, string $exp2, string $indent) : string
	{
		if (!$this->nullable) {
			return $this->notNullEq($exp1, $exp2, $indent);
		}
		$eq = $this->notNullEq($exp1, $exp2, $indent."\t\t");
		return "(($exp1 === null && $exp2 === null)\n$indent\t|| ($exp1 !== null && $exp2 !== null\n$indent\t\t&& $eq))";
	}

	public function getArgumentHint() : string
	{
		return ($this->nullable ? '?' : '').'array';
	}

	public function getPhpDocHint() : string
	{
		$inner = $this->inner_type->getPhpDocHint();
		return \str_replace('|', '[]|', $inner).'[]'.($this->nullable ? '|null' : '');
	}

	public function createNullable() : TypeDef
	{
		$x = clone $this;
		$x->nullable = true;
		return $x;
	}

}
