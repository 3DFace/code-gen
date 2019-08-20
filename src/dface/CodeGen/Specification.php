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
	/** @var bool */
	private $deprecated;
	/** @var int */
	private $modified;

	/**
	 * Specification constructor.
	 * @param ClassName $className
	 * @param FieldDef[] $fields
	 * @param string[] $interfaces
	 * @param string[] $traits
	 * @param bool $deprecated
	 * @param $modified
	 */
	public function __construct(
		ClassName $className,
		array $fields,
		array $interfaces,
		array $traits,
		$deprecated,
		$modified
	){
		$this->className = $className;
		$this->fields = $fields;
		$this->interfaces = $interfaces;
		$this->traits = $traits;
		$this->deprecated = $deprecated;
		$this->modified = $modified;
	}

	public function getClassName(){
		return $this->className;
	}

	public function getFields(){
		return $this->fields;
	}

	public function getInterfaces(){
		return $this->interfaces;
	}

	public function getTraits(){
		return $this->traits;
	}

    public function getDeprecated(){
        return $this->deprecated;
    }

	public function getModified(){
		return $this->modified;
	}

}
