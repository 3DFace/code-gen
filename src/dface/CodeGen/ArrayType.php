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

	function getSerializer($value_expression){
		if(is_a($this->innerType, ScalarType::class)){
			return $value_expression;
		}else{
			$type_hint = $this->innerType->getArgumentHint();
			return "array_map(function($type_hint \$x){\n".
			"\t\t\t\t".'return '.$this->innerType->getSerializer('$x').";\n".
			"\t\t\t}, $value_expression)";
		}
	}

	function getDeserializer($value_expression){
		return "array_map(function(\$x){\n".
		"\t\t\t".'return '.$this->innerType->getDeserializer('$x').";\n".
		"\t\t}, $value_expression)";
	}

	function getArgumentHint(){
		return 'array';
	}

	function getPhpDocHint(){
		$inner = $this->innerType->getPhpDocHint();
		return str_replace('|', '[]|', $inner).'[]';
	}

}
