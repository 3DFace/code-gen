<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class Specification
{

	private ClassName $class_name;
	/** @var FieldDef[] */
	private array $fields;
	/** @var string[] */
	private array $interfaces;
	/** @var string[] */
	private array $traits;
	private bool $deprecated;
	private int $modified;

	public function __construct(
		ClassName $class_name,
		array $fields,
		array $interfaces,
		array $traits,
		bool $deprecated,
		int $modified
	) {
		$this->class_name = $class_name;
		$this->fields = $fields;
		$this->interfaces = $interfaces;
		$this->traits = $traits;
		$this->deprecated = $deprecated;
		$this->modified = $modified;
	}

	public function getClassName() : ClassName
	{
		return $this->class_name;
	}

	/**
	 * @return FieldDef[]
	 */
	public function getFields() : array
	{
		return $this->fields;
	}

	/**
	 * @return string[]
	 */
	public function getInterfaces() : array
	{
		return $this->interfaces;
	}

	/**
	 * @return string[]
	 */
	public function getTraits() : array
	{
		return $this->traits;
	}

	public function getDeprecated() : bool
	{
		return $this->deprecated;
	}

	public function getModified() : int
	{
		return $this->modified;
	}

}
