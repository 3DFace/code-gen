<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class FieldDef {

	/** @var string */
	private $name;
	/** @var string|TypeDef */
	private $type;
	/** @var bool */
	private $hasConstructorDefault;
	/** @var mixed */
	private $constructorDefault;
	/** @var bool */
	private $hasSerializedDefault;
	/** @var mixed */
	private $serializedDefault;
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
	 * @param array $constructor_default
	 * @param array $serialized_default
	 * @param bool $wither
	 * @param bool $setter
	 */
	public function __construct($name, $type, array $aliases, $constructor_default, array $serialized_default, $wither, $setter){
		$this->name = $name;
		$this->type = $type;
		$this->aliases = $aliases;
		$this->hasConstructorDefault = $constructor_default[0];
		$this->constructorDefault = $constructor_default[1];
		$this->hasSerializedDefault = $constructor_default[0] || $serialized_default[0];
		$this->serializedDefault = $serialized_default[0] ? $serialized_default[1] : $constructor_default[1];
		$this->wither = $wither;
		$this->setter = $setter;
	}

	function getName(){
		return $this->name;
	}

	function getType(){
		return $this->type;
	}

	function hasConstructorDefault(){
		return $this->hasConstructorDefault;
	}

	function getConstructorDefault(){
		return $this->constructorDefault;
	}

	function hasSerializedDefault(){
		return $this->hasSerializedDefault;
	}

	function getSerializedDefault(){
		return $this->serializedDefault;
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
