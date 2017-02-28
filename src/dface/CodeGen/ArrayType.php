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
		}else{
			$type_hint = $this->innerType->getArgumentHint();
			return "$value_expression !== null ? array_map(function ($type_hint \$x){\n".
				$indent."\t".'return '.$this->innerType->getSerializer('$x', $indent."\t").";\n".
				$indent."}, $value_expression) : null";
		}
	}

	function getDeserializer($value_expression, $indent){
		return "$value_expression !== null ? array_map(function (\$x){\n".
			$indent."\t".'return '.$this->innerType->getDeserializer('$x', $indent."\t").";\n".
			$indent."}, $value_expression) : null";
	}

	function getArgumentHint(){
		return 'array';
	}

	function getPhpDocHint(){
		$inner = $this->innerType->getPhpDocHint();
		return str_replace('|', '[]|', $inner).'[]';
	}

}
