<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class DynamicTypeDef implements TypeDef {

	/** @var string */
	private $className;

	public function __construct(ClassName $dataClassName){
		$this->className = $dataClassName;
	}

	function getUses($namespace){
		return $namespace !== $this->className->getNamespace() ? [$this->className->getFullName()] : [];
	}

	function getSerializer($value_expression){
		return $value_expression." !==null ? ".$value_expression."->jsonSerialize() : null";
	}

	function getDeserializer($value_expression){
		return $this->className->getShortName()."::deserialize($value_expression)";
	}

	function getArgumentHint(){
		return$this->className->getShortName();
	}

	function getPhpDocHint(){
		return $this->className->getShortName();
	}

}
