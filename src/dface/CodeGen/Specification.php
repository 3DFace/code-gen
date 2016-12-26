<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class Specification {

	/** @var ClassName */
	private $className;
	/** @var FieldDef[] */
	private $fields;
	/** @var ClassName[] */
	private $interfaces;

	/**
	 * Specification constructor.
	 * @param ClassName $className
	 * @param FieldDef[] $fields
	 * @param ClassName[] $interfaces
	 */
	public function __construct(ClassName $className, array $fields, array $interfaces = []){
		$this->className = $className;
		$this->fields = $fields;
		$this->interfaces = $interfaces;
	}

	function getClassName(){
		return $this->className;
	}

	function getFields(){
		return $this->fields;
	}

	function getInterfaces(){
		return $this->interfaces;
	}

}
