<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class DynamicTypeDef implements TypeDef {

	/** @var string */
	private $dataClassName;
	/** @var string */
	private $shortDataClassName;
	/** @var string */
	private $namespace;

	public function __construct(string $dataClassName){
		$this->dataClassName = $dataClassName;
		$x = explode('\\', $dataClassName);
		$this->shortDataClassName = array_pop($x);
		$this->namespace = implode("\\", $x);
	}

	function getUses(string $namespace){
		return $namespace !== $this->namespace ? [$this->dataClassName] : [];
	}

	function getSerializer(string $value_expression){
		return $value_expression." !==null ? ".$value_expression."->jsonSerialize() : null";
	}

	function getDeserializer(string $value_expression){
		return $this->shortDataClassName."::deserialize($value_expression)";
	}

	function getArgumentHint(){
		return $this->shortDataClassName;
	}

	function getPhpDocHint(){
		return $this->shortDataClassName;
	}

}
