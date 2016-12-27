<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class Specification {

	/** @var ClassName */
	private $className;
	/** @var FieldDef[] */
	private $fields;
	/** @var string[] */
	private $interfaces;
	/** @var string[] */
	private $traits;

	/**
	 * Specification constructor.
	 * @param ClassName $className
	 * @param FieldDef[] $fields
	 * @param string[] $interfaces
	 * @param string[] $traits
	 */
	public function __construct(
		ClassName $className,
		array $fields,
		array $interfaces,
		array $traits
	){
		$this->className = $className;
		$this->fields = $fields;
		$this->interfaces = $interfaces;
		$this->traits = $traits;
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

	function getTraits(){
		return $this->traits;
	}

}
