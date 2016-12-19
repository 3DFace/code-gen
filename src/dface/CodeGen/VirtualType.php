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
		$this->baseNameSpace = '\\'.trim($baseNameSpace, '\\');
	}

	function getUses($namespace){
		return [];
	}

	function getSerializer($value_expression){
		return "call_user_func(function(\$val){\n".
			"\t\t\t"."if(\$val === null){\n".
			"\t\t\t\t"."return null;\n".
			"\t\t\t"."}elseif(\$val instanceof \\JsonSerializable){\n".
			"\t\t\t\t"."\$class = str_replace('$this->baseNameSpace'.'\\\\', '', '\\\\'.get_class(\$val));\n".
			"\t\t\t\t"."return [\$class, \$val->jsonSerialize()];\n".
			"\t\t\t"."}else{\n".
			"\t\t\t\t"."throw new \\InvalidArgumentException(\"Cant serialize type \".gettype(\$val));\n".
			"\t\t\t"."}\n".
			"\t\t}, $value_expression)";
	}

	function getDeserializer($value_expression){
		return "call_user_func(function(\$val){\n".
			"\t\t\t"."if(\$val === null){\n".
			"\t\t\t\t"."return null;\n".
			"\t\t\t"."}elseif(is_array(\$val)){\n".
			"\t\t\t\t"."list(\$type, \$serialized) = \$val;\n".
			"\t\t\t\t"."\$className = substr(\$type, 0, 1) === '\\\\' ? \$type : ('$this->baseNameSpace'.'\\\\'.\$type);\n".
			"\t\t\t\t"."return call_user_func([\$className, 'deserialize'], \$serialized);\n".
			"\t\t\t"."}else{\n".
			"\t\t\t\t"."throw new \\InvalidArgumentException(\"Cant serialize type \".gettype(\$val));\n".
			"\t\t\t"."}\n".
			"\t\t}, $value_expression)";
	}

	function getArgumentHint(){
		return '';
	}

	function getPhpDocHint(){
		return '\\JsonSerializable|null';
	}

}
