<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class ClassName
{

	private string $fullName;
	private string $namespace;
	private string $shortName;

	public function __construct(string $fullName)
	{
		$parts = \preg_split("|\\\\|", $fullName, -1, PREG_SPLIT_NO_EMPTY);
		$this->shortName = \array_pop($parts);
		$this->namespace = \implode("\\", $parts);
		$this->fullName = $this->namespace.'\\'.$this->shortName;
	}

	public function getFullName() : string
	{
		return $this->fullName;
	}

	public function getNamespace() : string
	{
		return $this->namespace;
	}

	public function getShortName() : string
	{
		return $this->shortName;
	}

	public function __toString() : string
	{
		return $this->fullName;
	}

}
