<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class ArrayType implements TypeDef {

	/** @var TypeDef */
	private $innerType;

	public function __construct(TypeDef $innerType){
		$this->innerType = $innerType;
	}

	public function getUses($namespace){
		return $this->innerType->getUses($namespace);
	}

	public function getSerializer($value_expression, $null_able, $indent){
		if(\is_a($this->innerType, ScalarType::class)){
			return $value_expression;
		}
		$type_hint = $this->innerType->getArgumentHint();
		return ($null_able ? "$value_expression === null ? null : " : '')."\\array_map(function ($type_hint \$x){\n".
			$indent."\t".'return '.$this->innerType->getSerializer('$x', false, $indent."\t").";\n".
			$indent."}, $value_expression)";
	}

	public function getDeserializer($target, $value_expression, $indent){
		return "$target = $value_expression !== null ? \\array_map(function (\$x){\n".
			$indent."\t".$this->innerType->getDeserializer('$x','$x', $indent."\t").
			$indent."\t".'return $x'.";\n".
			$indent."}, $value_expression) : null;\n";
	}

	public function getArgumentHint(){
		return 'array';
	}

	public function getPhpDocHint(){
		$inner = $this->innerType->getPhpDocHint();
		return \str_replace('|', '[]|', $inner).'[]';
	}

}
