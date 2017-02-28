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
		}else{
			return "$value_expression !== null ? call_user_func(function (array \$map){\n".
				$indent."\t"."\$x = [];\n".
				$indent."\t"."foreach(\$map as \$k => \$v){\n".
				$indent."\t\t"."/** @var \$v \\JsonSerializable */\n".
				$indent."\t\t".'$x[$k] = '.$this->innerType->getSerializer('$v', $indent."\t\t").";\n".
				$indent."\t"."}\n".
				$indent."\t"."return \$x;\n".
				$indent."}, $value_expression) : null";
		}
	}

	function getDeserializer($value_expression, $indent){
		return "$value_expression !== null ? call_user_func(function (array \$map){\n".
			$indent."\t"."\$x = [];\n".
			$indent."\t"."foreach(\$map as \$k => \$v){\n".
			$indent."\t\t".'$x[$k] = '.$this->innerType->getDeserializer('$v', $indent."\t\t").";\n".
			$indent."\t"."}\n".
			$indent."\t"."return \$x;\n".
			$indent."}, $value_expression) : null";
	}

	function getArgumentHint(){
		return 'array';
	}

	function getPhpDocHint(){
		return $this->innerType->getPhpDocHint().'[]';
	}

}
