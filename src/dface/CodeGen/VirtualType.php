<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class VirtualType implements TypeDef {

	/** @var string */
	private $baseNameSpace;
	/** @var ClassName */
	private $baseType;

	/**
	 * VirtualType constructor.
	 * @param string $baseNameSpace
	 * @param string $baseType
	 */
	public function __construct($baseNameSpace = null, $baseType = \JsonSerializable::class){
		$this->baseNameSpace = strlen($baseNameSpace) ? trim($baseNameSpace, '\\') : null;
		$this->baseType = new ClassName($baseType);
	}

	function getUses($namespace){
		return $this->baseType->getNamespace() === $namespace ? [] : [$this->baseType->getFullName()];
	}

	function getSerializer($value_expression, $indent){
		$classNameToTypeTransform = '';
		if($this->baseNameSpace !== null){
			$ns = str_replace('\\', '\\\\', $this->baseNameSpace);
			$classNameToTypeTransform =  $indent."\t\t"."\$type = str_replace('\\\\$ns\\\\', '', \$type);\n";
		}
		return "$value_expression !== null ? call_user_func(function (\$val){\n".
			$indent."\t"."if(\$val === null){\n".
			$indent."\t\t"."return null;\n".
			$indent."\t"."}elseif(\$val instanceof \\JsonSerializable){\n".
			$indent."\t\t"."\$type = '\\\\'.get_class(\$val);\n".
			$classNameToTypeTransform.
			$indent."\t\t"."return [\$type, \$val->jsonSerialize()];\n".
			$indent."\t"."}else{\n".
			$indent."\t\t"."throw new \\InvalidArgumentException('Cant serialize type '.gettype(\$val));\n".
			$indent."\t"."}\n".
			$indent."}, $value_expression) : null";
	}

	function getDeserializer($value_expression, $indent){
		$typeToClassNameTransform = $indent."\t\t"."\$className = \$type;\n";
		if($this->baseNameSpace !== null){
			$ns = str_replace('\\', '\\\\', $this->baseNameSpace);
			$typeToClassNameTransform =  $indent."\t\t"."\$className = \$type[0] === '\\\\' ? \$type : ('$ns\\\\'.\$type);\n";
		}
		return "$value_expression !== null ? call_user_func(function (\$val){\n".
			$indent."\t"."if(\$val === null){\n".
			$indent."\t\t"."return null;\n".
			$indent."\t"."}elseif(is_array(\$val)){\n".
			$indent."\t\t"."list(\$type, \$serialized) = \$val;\n".
			$typeToClassNameTransform.
			$indent."\t\t"."return \$className::deserialize(\$serialized);\n".
			$indent."\t"."}else{\n".
			$indent."\t\t"."throw new \\InvalidArgumentException('Cant deserialize type '.gettype(\$val));\n".
			$indent."\t"."}\n".
			$indent."}, $value_expression) : null";
	}

	function getArgumentHint(){
		return $this->baseType->getShortName();
	}

	function getPhpDocHint(){
		return $this->baseType->getShortName();
	}

}
