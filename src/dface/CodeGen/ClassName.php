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

	public function __construct(string $fullName){
		$parts = preg_split("|\\|", $fullName, -1, PREG_SPLIT_NO_EMPTY);
		$this->shortName = array_pop($parts);
		$this->namespace = implode("\\", $parts);
		$this->fullName = $this->namespace.'\\'.$this->shortName;
	}

	function getFullName(){
		return $this->fullName;
	}

	function getNamespace(){
		return $this->namespace;
	}

	function getShortName(){
		return $this->shortName;
	}

	function __toString(){
		return $this->fullName;
	}

}
