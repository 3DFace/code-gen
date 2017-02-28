<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class MixedType implements TypeDef {

	function getUses($namespace){
		return [];
	}

	function getSerializer($value_expression, $indent){
		return $value_expression;
	}

	function getDeserializer($value_expression, $indent){
		return $value_expression;
	}

	function getArgumentHint(){
		return '';
	}

	function getPhpDocHint(){
		return 'mixed';
	}

}
