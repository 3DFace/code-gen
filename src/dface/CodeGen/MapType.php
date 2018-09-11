<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class MapType implements TypeDef {

	/** @var TypeDef */
	private $innerType;

	public function __construct(TypeDef $innerType){
		$this->innerType = $innerType;
	}

	public function getUses($namespace){
		return $this->innerType->getUses($namespace);
	}

	public function getSerializer($value_expression, $null_able, $indent){
		if(is_a($this->innerType, ScalarType::class)){
			return $value_expression;
		}
		$inner_hint = $this->innerType->getPhpDocHint();
		return ($null_able ? "$value_expression === null ? null : " : '')."\call_user_func(function (array \$map){\n".
			$indent."\t"."\$x = [];\n".
			$indent."\t"."foreach(\$map as \$k => \$v){\n".
			$indent."\t\t/** @var $inner_hint \$v */\n".
			$indent."\t\t".'$x[$k] = '.$this->innerType->getSerializer('$v', false, $indent."\t\t").";\n".
			$indent."\t"."}\n".
			$indent."\t"."return \$x;\n".
			$indent."}, $value_expression)";
	}

	public function getDeserializer($target, $value_expression, $indent){
		$exp = $this->innerType->getDeserializer('$x[$k]', '$v', $indent."\t\t");
		return "$target = $value_expression !== null ? \call_user_func(function (array \$map){\n".
			$indent."\t"."\$x = [];\n".
			$indent."\t"."foreach(\$map as \$k => \$v){\n".
			$indent."\t\t".$exp.
			$indent."\t"."}\n".
			$indent."\t"."return \$x;\n".
			$indent."}, $value_expression) : null;\n";
	}

	public function getArgumentHint(){
		return 'array';
	}

	public function getPhpDocHint(){
		return $this->innerType->getPhpDocHint().'[]';
	}

}
