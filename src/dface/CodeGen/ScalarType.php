<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class ScalarType implements TypeDef {

	/** @var string */
	private $type;

	public function __construct(string $type){
		$this->type = $type;
	}

	function getUses(string $namespace){
		return [];
	}

	function getSerializer(string $value_expression){
		return $value_expression;
	}

	function getDeserializer(string $value_expression){
		return "($this->type) $value_expression";
	}

	function getArgumentHint(){
		return $this->type;
	}

	function getPhpDocHint(){
		return $this->type;
	}

}
