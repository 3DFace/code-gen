<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class DynamicTypeDef implements TypeDef {

	/** @var string */
	private $className;

	public function __construct(ClassName $dataClassName){
		$this->className = $dataClassName;
	}

	public function getUses($namespace){
		return $namespace !== $this->className->getNamespace() ? [$this->className->getFullName()] : [];
	}

	public function getSerializer($value_expression, $null_able, $indent){
		return ($null_able ? "$value_expression === null ? null : " : '').$value_expression.'->jsonSerialize()';
	}

	public function getDeserializer($target, $value_expression, $indent){
		$deserializer = "$target = $value_expression !== null ? ".$this->className->getShortName()."::deserialize($value_expression) : null";
		$body = "try {\n";
		$body .= $indent."\t$deserializer;\n";
		$body .= $indent."}catch (\Exception \$e){\n";
		$body .= $indent."\t"."throw new \InvalidArgumentException('Deserialization error: '.\$e->getMessage(), 0, \$e);\n";
		$body .= $indent."}\n";
		return $body;
	}

	public function getArgumentHint(){
		return$this->className->getShortName();
	}

	public function getPhpDocHint(){
		return $this->className->getShortName();
	}

}
