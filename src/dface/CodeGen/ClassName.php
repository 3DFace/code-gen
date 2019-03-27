<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class ClassName {

	/** @var string */
	private $fullName;
	/** @var string */
	private $namespace;
	/** @var string */
	private $shortName;

	/**
	 * ClassName constructor.
	 * @param string $fullName
	 */
	public function __construct($fullName){
		$parts = \preg_split("|\\\\|", $fullName, -1, PREG_SPLIT_NO_EMPTY);
		$this->shortName = \array_pop($parts);
		$this->namespace = \implode("\\", $parts);
		$this->fullName = $this->namespace.'\\'.$this->shortName;
	}

	public function getFullName(){
		return $this->fullName;
	}

	public function getNamespace(){
		return $this->namespace;
	}

	public function getShortName(){
		return $this->shortName;
	}

	public function __toString(){
		return $this->fullName;
	}

}
