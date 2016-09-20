<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class FieldDef {

	/** @var string */
	private $name;
	/** @var string */
	private $type;
	/** @var bool */
	private $hasDefault;
	/** @var mixed */
	private $default;
	/** @var bool */
	private $wither = false;
	/** @var string[] */
	private $aliases;

	public function __construct(string $name, string $type, array $aliases = []){
		$this->name = $name;
		$this->type = $type;
		$this->aliases = $aliases;
	}

	function setDefault($default){
		$this->default = $default;
		$this->hasDefault = true;
	}

	function setWither(bool $bool){
		$this->wither = $bool;
	}

	function getName(){
		return $this->name;
	}

	function getType(){
		return $this->type;
	}

	function hasDefault(){
		return $this->hasDefault;
	}

	function getDefault(){
		return $this->default;
	}

	function getWither(){
		return $this->wither;
	}

	function getAliases(){
		return $this->aliases;
	}

}
