<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class ScalarType implements TypeDef {

	/** @var string */
	private $type;

	/**
	 * ScalarType constructor.
	 * @param string $type
	 */
	public function __construct($type){
		$this->type = $type;
	}

	public function getUses($namespace){
		return [];
	}

	public function getSerializer($value_expression, $null_able, $indent){
		return $value_expression;
	}

	public function getDeserializer($target, $value_expression, $indent){
		return "$target = $value_expression !== null ? ($this->type)$value_expression : null;\n";
	}

	public function getArgumentHint(){
		return $this->type;
	}

	public function getPhpDocHint(){
		return $this->type;
	}

}
