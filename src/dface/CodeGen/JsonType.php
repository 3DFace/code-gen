<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class JsonType implements TypeDef {

	/** @var TypeDef */
	private $innerType;
	/** @var int */
	private $encode_options;
	/** @var int */
	private $decode_options;

	/**
	 * JsonType constructor.
	 * @param TypeDef $innerType
	 * @param int $encode_options
	 * @param int $decode_options
	 */
	public function __construct(TypeDef $innerType, $encode_options = 0, $decode_options = 0) {
		$this->innerType = $innerType;
		$this->encode_options = $encode_options;
		$this->decode_options = $decode_options;
	}

	function getUses($namespace) {
		return $this->innerType->getUses($namespace);
	}

	function getSerializer($value_expression, $null_able, $indent) {
		$exp = $this->innerType->getSerializer('$val', false, $indent."\t");
		return ($null_able ? "$value_expression === null ? null : " : '')."call_user_func(function (\JsonSerializable \$val){\n".
			$indent."\t"."\$x = $exp;\n".
			$indent."\t"."return json_encode(\$x, $this->encode_options);\n".
			$indent."}, $value_expression)";
	}

	function getDeserializer($target, $value_expression, $indent) {
		$exp = $this->innerType->getDeserializer('$x', '$x', $indent."\t");
		return "$target = $value_expression !== null ? call_user_func(function (\$val){\n".
			$indent."\t"."\$x = json_decode(\$val, true, 512, $this->decode_options);\n".
			$indent."\t".$exp.
			$indent."\t"."return \$x;\n".
			$indent."}, $value_expression) : null;\n";
	}

	function getArgumentHint() {
		return $this->innerType->getArgumentHint();
	}

	function getPhpDocHint() {
		return $this->innerType->getPhpDocHint();
	}

}
