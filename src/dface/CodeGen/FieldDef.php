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

	/**
	 * FieldDef constructor.
	 * @param string $name
	 * @param string $type
	 * @param string[] $aliases
	 * @param bool $has_default
	 * @param null $default
	 * @param bool $wither
	 */
	public function __construct($name, $type, array $aliases = [], $has_default = false, $default = null, $wither = false){
		$this->name = $name;
		$this->type = $type;
		$this->aliases = $aliases;
		$this->hasDefault = $has_default;
		$this->default = $default;
		$this->wither = $wither;
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
