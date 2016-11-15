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

	function getSerializer($value_expression){
		return $value_expression;
	}

	function getDeserializer($value_expression){
		return "($this->type) $value_expression";
	}

	function getArgumentHint(){
		return $this->type;
	}

	function getPhpDocHint(){
		return $this->type;
	}

}
