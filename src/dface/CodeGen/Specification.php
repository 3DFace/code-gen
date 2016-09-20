<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class Specification {

	/** @var ClassName */
	private $className;
	/** @var FieldDef[] */
	private $fields;

	/**
	 * Specification constructor.
	 * @param ClassName $className
	 * @param FieldDef[] $fields
	 */
	public function __construct(ClassName $className, array $fields){
		$this->className = $className;
		$this->fields = $fields;
	}

	function getClassName(){
		return $this->className;
	}

	function getFields(){
		return $this->fields;
	}

}
