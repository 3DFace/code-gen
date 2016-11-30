<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class VirtualType implements TypeDef {

	/** @var string */
	private $baseNameSpace;

	/**
	 * VirtualType constructor.
	 * @param string $baseNameSpace
	 */
	public function __construct($baseNameSpace){
		$this->baseNameSpace = $baseNameSpace;
	}

	static function serialize($val, $baseNamespace){
		if($val === null){
			return null;
		}elseif($val instanceof \JsonSerializable){
			$class = str_replace($baseNamespace.'\\', '', get_class($val));
			return [$class, $val->jsonSerialize()];
		}else{
			throw new \InvalidArgumentException("Cant serialize type ".gettype($val));
		}
	}

	static function deserialize($val, $baseNamespace){
		if($val === null){
			return null;
		}elseif(is_array($val)){
			list($type, $serialized) = $val;
			$className = $baseNamespace.'\\'.$type;
			return call_user_func([$className, 'deserialize'], $serialized);
		}else{
			throw new \InvalidArgumentException("Cant deserialize type ".gettype($val));
		}
	}

	function getUses($namespace){
		return [self::class];
	}

	function getSerializer($value_expression){
		return 'VirtualType::serialize('.$value_expression.', "'.addslashes($this->baseNameSpace).'")';
	}

	function getDeserializer($value_expression){
		return 'VirtualType::deserialize('.$value_expression.', "'.addslashes($this->baseNameSpace).'")';
	}

	function getArgumentHint(){
		return '';
	}

	function getPhpDocHint(){
		return '\\JsonSerializable|null';
	}

}
