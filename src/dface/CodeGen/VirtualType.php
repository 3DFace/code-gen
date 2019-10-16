<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class VirtualType implements TypeDef {

	/** @var ClassName */
	private $baseType;
	/** @var array[] */
	private $types;

	public function __construct($baseType, array $typeToIdMap){
		$this->baseType = new ClassName($baseType);
		$this->types = [];
		foreach($typeToIdMap as $className => $id){
			$this->types[] = [new ClassName($className), $id];
		}
	}

	public function getUses($namespace){
		$uses = [];
		if($this->baseType->getNamespace() !== $namespace){
			$uses[$this->baseType->getFullName()] = 1;
		}
		foreach($this->types as $class_and_id){
			/** @var ClassName $class */
			$class = $class_and_id[0];
			if($class->getNamespace() !== $namespace){
				$uses[$class->getFullName()] = 1;
			}
		}
		return \array_keys($uses);
	}

	public function getSerializer($value_expression, $null_able, $indent){
		$result = ($null_able ? "$value_expression === null ? null : " : '')."\call_user_func(function (\$val){\n";

		foreach($this->types as $class_and_id){
			/** @var ClassName $class */
			list($class, $id) = $class_and_id;
			$short = $class->getShortName();
			$result .=
				$indent."\t"."if(\$val instanceof $short){\n".
				$indent."\t\t"."return [$id, \$val->jsonSerialize()];\n".
				$indent."\t"."}\n";
		}
		$result .=
			$indent."\t"."throw new \\RuntimeException('Unsupported virtual type '.\gettype(\$val));\n".
			$indent."}, $value_expression)";
		return $result;
	}

	public function getDeserializer($target, $value_expression, $indent){
		$result = "$target = $value_expression !== null ? \call_user_func(function (\$val){\n".
			$indent."\t"."if(\\is_array(\$val)){\n".
			$indent."\t\t"."list(\$type, \$serialized) = \$val;\n".
			$indent."\t\t"."switch(\$type){\n";
		foreach($this->types as $class_and_id){
			/** @var ClassName $class */
			list($class, $id) = $class_and_id;
			$short = $class->getShortName();
			$result .=
				$indent."\t\t\t"."case $id:\n".
				$indent."\t\t\t\t"."return $short::deserialize(\$serialized);\n";
		}
		$result .=
			$indent."\t\t\t"."default:\n".
			$indent."\t\t\t\t"."throw new \\InvalidArgumentException('Unknown type id: '.\$type);\n".
			$indent."\t\t"."}\n".
			$indent."\t"."}else{\n".
			$indent."\t\t"."throw new \\InvalidArgumentException('Cant deserialize '.\gettype(\$val));\n".
			$indent."\t"."}\n".
			$indent."}, $value_expression) : null;\n";
		return $result;
	}

	public function getArgumentHint(){
		if(\count($this->types) === 1){
			return $this->getPhpDocHint();
		}
		return $this->baseType->getShortName();
	}

	public function getPhpDocHint(){
		$shorts = [];
		foreach ($this->types as $class_and_id){
			/** @var ClassName $class */
			list($class) = $class_and_id;
			$shorts[] = $class->getShortName();
		}
		return \implode('|', $shorts);
	}

}
