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
	/** @var bool */
	private $serialize_plain;

	/**
	 * JsonType constructor.
	 * @param TypeDef $innerType
	 * @param int $encode_options
	 * @param int $decode_options
	 */
	public function __construct(TypeDef $innerType, $encode_options = 0, $decode_options = 0, $serialize_plain = false) {
		$this->innerType = $innerType;
		$this->encode_options = $encode_options;
		$this->decode_options = $decode_options;
		$this->serialize_plain = $serialize_plain;
	}

	public function getUses($namespace) {
		return $this->innerType->getUses($namespace);
	}

	public function getSerializer($value_expression, $null_able, $indent) {
		if($this->serialize_plain){
			return $this->innerType->getSerializer($value_expression, $null_able, $indent);
		}
		$exp = $this->innerType->getSerializer('$val', false, $indent."\t");
		return ($null_able ? "$value_expression === null ? null : " : '')."\call_user_func(function (\JsonSerializable \$val){\n".
			$indent."\t"."\$x = $exp;\n".
			$indent."\t"."return \\json_encode(\$x, $this->encode_options);\n".
			$indent."}, $value_expression)";
	}

	public function getDeserializer($target, $value_expression, $indent) {
		$exp = $this->innerType->getDeserializer('$x', '$x', $indent."\t");
		return "$target = $value_expression !== null ? \call_user_func(function (\$val){\n".
			$indent."\t"."\$x = \\json_decode(\$val, true, 512, $this->decode_options);\n".
			$indent."\t".$exp.
			$indent."\t"."return \$x;\n".
			$indent."}, $value_expression) : null;\n";
	}

	public function getArgumentHint() {
		return $this->innerType->getArgumentHint();
	}

	public function getPhpDocHint() {
		return $this->innerType->getPhpDocHint();
	}

}
