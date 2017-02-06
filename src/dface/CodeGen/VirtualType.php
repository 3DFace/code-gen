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

	function getSerializer($value_expression){
		$classNameToTypeTransform = '';
		if($this->baseNameSpace !== null){
			$ns = str_replace('\\', '\\\\', $this->baseNameSpace);
			$classNameToTypeTransform =  "\t\t\t\t"."\$type = str_replace('\\\\$ns\\\\', '', \$type);\n";
		}
		return "call_user_func(function(\$val){\n".
			"\t\t\t"."if(\$val === null){\n".
			"\t\t\t\t"."return null;\n".
			"\t\t\t"."}elseif(\$val instanceof \\JsonSerializable){\n".
			"\t\t\t\t"."\$type = '\\\\'.get_class(\$val);\n".
			$classNameToTypeTransform.
			"\t\t\t\t"."return [\$type, \$val->jsonSerialize()];\n".
			"\t\t\t"."}else{\n".
			"\t\t\t\t"."throw new \\InvalidArgumentException('Cant serialize type '.gettype(\$val));\n".
			"\t\t\t"."}\n".
			"\t\t}, $value_expression)";
	}

	function getDeserializer($value_expression){
		$typeToClassNameTransform = "\t\t\t\t"."\$className = \$type;\n";
		if($this->baseNameSpace !== null){
			$ns = str_replace('\\', '\\\\', $this->baseNameSpace);
			$typeToClassNameTransform =  "\t\t\t\t"."\$className = \$type[0] === '\\\\' ? \$type : ('$ns\\\\'.\$type);\n";
		}
		return "call_user_func(function(\$val){\n".
			"\t\t\t"."if(\$val === null){\n".
			"\t\t\t\t"."return null;\n".
			"\t\t\t"."}elseif(is_array(\$val)){\n".
			"\t\t\t\t"."list(\$type, \$serialized) = \$val;\n".
			$typeToClassNameTransform.
			"\t\t\t\t"."return \$className::deserialize(\$serialized);\n".
//			"\t\t\t\t"."return call_user_func([\$className, 'deserialize'], \$serialized);\n".
			"\t\t\t"."}else{\n".
			"\t\t\t\t"."throw new \\InvalidArgumentException('Cant deserialize type '.gettype(\$val));\n".
			"\t\t\t"."}\n".
			"\t\t}, $value_expression)";
	}

	function getArgumentHint(){
		return $this->baseType->getShortName();
	}

	function getPhpDocHint(){
		return $this->baseType->getShortName();
	}

}
