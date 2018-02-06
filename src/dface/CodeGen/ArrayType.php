<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class ArrayType implements TypeDef {

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
		$type_hint = $this->innerType->getArgumentHint();
		return "$value_expression !== null ? array_map(function ($type_hint \$x){\n".
			$indent."\t".'return '.$this->innerType->getSerializer('$x', $indent."\t").";\n".
			$indent."}, $value_expression) : null";
	}

	function getDeserializer($target, $value_expression, $indent){
		return "$target = $value_expression !== null ? array_map(function (\$x){\n".
			$indent."\t".$this->innerType->getDeserializer('$x','$x', $indent."\t").";\n".
			$indent."\t".'return $x'.";\n".
			$indent."}, $value_expression) : null;\n";
	}

	function getArgumentHint(){
		return 'array';
	}

	function getPhpDocHint(){
		$inner = $this->innerType->getPhpDocHint();
		return str_replace('|', '[]|', $inner).'[]';
	}

}
