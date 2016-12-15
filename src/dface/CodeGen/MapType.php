<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class MapType implements TypeDef {

	/** @var TypeDef */
	private $innerType;

	public function __construct(TypeDef $innerType){
		$this->innerType = $innerType;
	}

	function getUses($namespace){
		return $this->innerType->getUses($namespace);
	}

	function getSerializer($value_expression){
		if(is_a($this->innerType, ScalarType::class)){
			return $value_expression;
		}else{
			return "call_user_func(function(\$map){\n".
				"\t\t\t"."\$x = [];\n".
				"\t\t\t"."foreach(\$map as \$k=>\$v){\n".
				"\t\t\t\t"."/** @var \$v \\JsonSerializable */\n".
				"\t\t\t\t"."\$x[\$k] = ".$this->innerType->getSerializer('$v').";\n".
				"\t\t\t"."}\n".
				"\t\t\t"."return \$x;\n".
				"\t\t}, $value_expression)";
		}
	}

	static function map($fn, $map){
		$result = [];
		foreach($map as $k=>$v){
			$result[$k] = $fn($v);
		}
		return $result;
	}

	function getDeserializer($value_expression){
		return "call_user_func(function(\$map){\n".
			"\t\t\t"."\$x = [];\n".
			"\t\t\t"."foreach(\$map as \$k=>\$v){\n".
			"\t\t\t\t"."\$x[\$k] = ".$this->innerType->getDeserializer('$v').";\n".
			"\t\t\t"."}\n".
			"\t\t\t"."return \$x;\n".
			"\t\t}, $value_expression)";
	}

	function getArgumentHint(){
		return "array";
	}

	function getPhpDocHint(){
		return $this->innerType->getPhpDocHint().'[]';
	}

}
