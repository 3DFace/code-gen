<?php

namespace dface\CodeGen;

class ClassName
{

	private string $full_name;
	private string $namespace;
	private string $short_name;

	public function __construct(string $fullName)
	{
		$parts = \preg_split("|\\\\|", $fullName, -1, PREG_SPLIT_NO_EMPTY);
		$this->short_name = (string)\array_pop($parts);
		$this->namespace = \implode("\\", $parts);
		$this->full_name = $this->namespace.'\\'.$this->short_name;
	}

	public function getFullName() : string
	{
		return $this->full_name;
	}

	public function getNamespace() : string
	{
		return $this->namespace;
	}

	public function getShortName() : string
	{
		return $this->short_name;
	}

	public function __toString() : string
	{
		return $this->full_name;
	}

}
