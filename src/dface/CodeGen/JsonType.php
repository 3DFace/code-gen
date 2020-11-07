<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class JsonType implements TypeDef
{

	private TypeDef $innerType;
	private int $encode_options;
	private int $decode_options;
	private bool $serialize_plain;

	public function __construct(
		TypeDef $innerType,
		int $encode_options = 0,
		int $decode_options = 0,
		$serialize_plain = false
	) {
		$this->innerType = $innerType;
		$this->encode_options = $encode_options;
		$this->decode_options = $decode_options;
		$this->serialize_plain = $serialize_plain;
	}

	public function getUses(string $namespace) : array
	{
		$uses = $this->innerType->getUses($namespace);
		$uses[] = 'JsonSerializable';
		return $uses;
	}

	public function getSerializer(string $value_expression, bool $null_able, string $indent) : string
	{
		if ($this->serialize_plain) {
			return $this->innerType->getSerializer($value_expression, $null_able, $indent);
		}
		$exp = $this->innerType->getSerializer('$val', false, $indent."\t");
		return ($null_able ? "$value_expression === null ? null : " : '')."(static function (JsonSerializable \$val){\n".
			$indent."\t"."try {\n".
			$indent."\t\t"."\$x = $exp;\n".
			$indent."\t\t"."return \\json_encode(\$x, $this->encode_options | JSON_THROW_ON_ERROR);\n".
			$indent."\t}catch (\Exception \$e){\n".
			$indent."\t\t"."throw new \\InvalidArgumentException(\$e->getMessage(), 0, \$e);\n".
			$indent."\t}\n".
			$indent."})($value_expression)";
	}

	public function getDeserializer(string $l_value, string $indent) : string
	{
		return "if($l_value !== null){\n".
			$indent."\t"."try {\n".
			$indent."\t\t"."$l_value = \\json_decode($l_value, true, 512, $this->decode_options | JSON_THROW_ON_ERROR);\n".
			$indent."\t}catch (\Exception \$e){\n".
			$indent."\t\t"."throw new \\InvalidArgumentException(\$e->getMessage(), 0, \$e);\n".
			$indent."\t}\n".
			$indent."\t".$this->innerType->getDeserializer($l_value, $indent."\t").
			$indent."}\n";
	}

	public function getArgumentHint() : string
	{
		return $this->innerType->getArgumentHint();
	}

	public function getPhpDocHint() : string
	{
		return $this->innerType->getPhpDocHint();
	}

}
