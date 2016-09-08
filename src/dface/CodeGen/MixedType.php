<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class MixedType implements TypeDef {

	function getUses(string $namespace){
		return [];
	}

	function getSerializer(string $value_expression){
		return $value_expression;
	}

	function getDeserializer(string $value_expression){
		return $value_expression;
	}

	function getArgumentHint(){
		return "";
	}

	function getPhpDocHint(){
		return 'mixed';
	}

}
