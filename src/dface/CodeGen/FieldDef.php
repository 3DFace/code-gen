<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class FieldDef {

	/** @var string */
	private $name;
	/** @var string|TypeDef */
	private $type;
	/** @var bool */
	private $hasDefault;
	/** @var mixed */
	private $default;
	/** @var bool */
	private $wither = false;
	/** @var bool */
	private $setter = false;
	/** @var string[] */
	private $aliases;

	/**
	 * FieldDef constructor.
	 * @param string $name
	 * @param string|TypeDef $type
	 * @param string[] $aliases
	 * @param bool $has_default
	 * @param null $default
	 * @param bool $wither
	 * @param bool $setter
	 */
	public function __construct($name, $type, array $aliases = [], $has_default = false, $default = null, $wither = false, $setter = false){
		$this->name = $name;
		$this->type = $type;
		$this->aliases = $aliases;
		$this->hasDefault = $has_default;
		$this->default = $default;
		$this->wither = $wither;
		$this->setter = $setter;
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

	function getSetter(){
		return $this->setter;
	}

	function getAliases(){
		return $this->aliases;
	}

}
