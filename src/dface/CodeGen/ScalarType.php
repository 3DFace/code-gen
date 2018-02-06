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

	function getUses($namespace){
		return [];
	}

	function getSerializer($value_expression, $indent){
		return $value_expression;
	}

	function getDeserializer($target, $value_expression, $indent){
		return "$target = $value_expression !== null ? ($this->type)$value_expression : null;\n";
	}

	function getArgumentHint(){
		return $this->type;
	}

	function getPhpDocHint(){
		return $this->type;
	}

}
