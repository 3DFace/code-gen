<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class MapType implements TypeDef {

	/** @var TypeDef */
	private $innerType;

	public function __construct(TypeDef $innerType){
		$this->innerType = $innerType;
	}

	function getUses($namespace){
		return $this->innerType->getUses($namespace);
	}

	function getSerializer($value_expression, $indent){
		if(is_a($this->innerType, ScalarType::class)){
			return $value_expression;
		}
		$inner_hint = $this->innerType->getPhpDocHint();
		return "$value_expression !== null ? call_user_func(function (array \$map){\n".
			$indent."\t"."\$x = [];\n".
			$indent."\t"."foreach(\$map as \$k => \$v){\n".
			$indent."\t\t/** @var $inner_hint \$v */\n".
			$indent."\t\t".'$x[$k] = '.$this->innerType->getSerializer('$v', $indent."\t\t").";\n".
			$indent."\t"."}\n".
			$indent."\t"."return \$x;\n".
			$indent."}, $value_expression) : null";
	}

	function getDeserializer($target, $value_expression, $indent){
		$exp = $this->innerType->getDeserializer('$x[$k]', '$v', $indent."\t\t");
		return "$target = $value_expression !== null ? call_user_func(function (array \$map){\n".
			$indent."\t"."\$x = [];\n".
			$indent."\t"."foreach(\$map as \$k => \$v){\n".
			$indent."\t\t".$exp.
			$indent."\t"."}\n".
			$indent."\t"."return \$x;\n".
			$indent."}, $value_expression) : null;\n";
	}

	function getArgumentHint(){
		return 'array';
	}

	function getPhpDocHint(){
		return $this->innerType->getPhpDocHint().'[]';
	}

}
