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
	private $wither;
	/** @var bool */
	private $setter;
	/** @var string[] */
	private $aliases;
	/** @var bool */
	private $merged;
	/** @var bool */
	private $silent;
	/** @var bool */
	private $null_able;
	/** @var string */
	private $field_visibility;

	/**
	 * @param string $name
	 * @param string|TypeDef $type
	 * @param string[] $aliases
	 * @param array $constructor_default
	 * @param array $serialized_default
	 * @param bool $wither
	 * @param bool $setter
	 * @param $merged
	 * @param $silent
	 * @param $null_able
	 * @param $field_visibility
	 * @throws \InvalidArgumentException
	 */
	public function __construct($name, $type, array $aliases, $constructor_default, array $serialized_default,
		$wither, $setter, $merged, $silent, $null_able, $field_visibility){
		$this->name = $name;
		$this->type = $type;
		$this->aliases = $aliases;
		$this->hasConstructorDefault = $constructor_default[0];
		$this->constructorDefault = $constructor_default[1];
		$this->hasSerializedDefault = $constructor_default[0] || $serialized_default[0];
		$this->serializedDefault = $serialized_default[0] ? $serialized_default[1] : $constructor_default[1];
		$this->wither = $wither;
		$this->setter = $setter;
		$this->merged = $merged;
		$this->silent = $silent;
		$this->null_able = $null_able;
		$visibilitySet = ['private', 'protected', 'public', null];
		if(!in_array($field_visibility, $visibilitySet, true)){
			throw new \InvalidArgumentException('Fields visibility must be one of ['.implode(', ', $visibilitySet).']');
		}
		$this->field_visibility = $field_visibility;
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

	function getMerged(){
		return $this->merged;
	}

	function getSilent(){
		return $this->silent;
	}

	function getNullAble(){
		return $this->null_able;
	}

	public function getFieldVisibility(){
		return $this->field_visibility;
	}

}
