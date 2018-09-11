<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class MixedType implements TypeDef {

	public function getUses($namespace){
		return [];
	}

	public function getSerializer($value_expression, $null_able, $indent){
		return $value_expression;
	}

	public function getDeserializer($target, $value_expression, $indent){
		if($target === $value_expression){
			return '';
		}
		return "$target = $value_expression;\n";
	}

	public function getArgumentHint(){
		return '';
	}

	public function getPhpDocHint(){
		return 'mixed';
	}

}
