<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class MixedType implements TypeDef {

	function getUses($namespace){
		return [];
	}

	function getSerializer($value_expression, $null_able, $indent){
		return $value_expression;
	}

	function getDeserializer($target, $value_expression, $indent){
		if($target === $value_expression){
			return '';
		}
		return "$target = $value_expression;\n";
	}

	function getArgumentHint(){
		return '';
	}

	function getPhpDocHint(){
		return 'mixed';
	}

}
