<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class MapType implements TypeDef
{

	private TypeDef $innerType;

	public function __construct(TypeDef $innerType)
	{
		$this->innerType = $innerType;
	}

	public function getUses(string $namespace) : array
	{
		return $this->innerType->getUses($namespace);
	}

	public function getSerializer(string $value_expression, bool $null_able, string $indent) : string
	{
		if (\is_a($this->innerType, ScalarType::class)) {
			return $value_expression;
		}
		$inner_hint = $this->innerType->getPhpDocHint();
		return ($null_able ? "$value_expression === null ? null : " : '')."(static function (array \$map){\n".
			$indent."\t"."\$x = [];\n".
			$indent."\t"."foreach(\$map as \$k => \$v){\n".
			$indent."\t\t/** @var $inner_hint \$v */\n".
			$indent."\t\t".'$x[$k] = '.$this->innerType->getSerializer('$v', false, $indent."\t\t").";\n".
			$indent."\t"."}\n".
			$indent."\t"."return \$x ?: new \stdClass();\n".
			$indent."})($value_expression)";
	}

	public function getDeserializer(string $l_value, string $indent) : string
	{
		return "if($l_value !== null){\n".
			$indent."\t"."$l_value = (static function (\$map){\n".
			$indent."\t\t"."\$x = [];\n".
			$indent."\t\t"."foreach(\$map as \$k => \$v){\n".
			$indent."\t\t\t".$this->innerType->getDeserializer('$v', $indent."\t\t\t").
			$indent."\t\t\t\$x[\$k] = \$v;\n".
			$indent."\t\t"."}\n".
			$indent."\t\t"."return \$x;\n".
			$indent."\t})($l_value);\n".
			$indent."}\n";
	}

	public function getArgumentHint() : string
	{
		return 'array';
	}

	public function getPhpDocHint() : string
	{
		return $this->innerType->getPhpDocHint().'[]';
	}

}
