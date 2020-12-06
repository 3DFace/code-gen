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

	public function getSerializer(string $value_expression, string $indent) : string
	{
		if ($this->serialize_plain) {
			return $this->innerType->getSerializer($value_expression, $indent);
		}
		$exp = $this->innerType->getSerializer('$val', $indent."\t");
		return "(static function (\$val){\n".
			$indent."\t"."try {\n".
			$indent."\t\t"."\$x = $exp;\n".
			$indent."\t\t"."return \\json_encode(\$x, $this->encode_options | JSON_THROW_ON_ERROR);\n".
			$indent."\t}catch (\Exception \$e){\n".
			$indent."\t\t"."throw new \\InvalidArgumentException(\$e->getMessage(), 0, \$e);\n".
			$indent."\t}\n".
			$indent."})($value_expression)";
	}

	public function getDeserializer(string $value_expression, string $indent) : string
	{
		return "$value_expression === null ? null : (static function(\$x){\n".
			$indent."\t"."try {\n".
			$indent."\t\t"."\$decoded = \\json_decode(\$x, true, 512, $this->decode_options | JSON_THROW_ON_ERROR);\n".
			$indent."\t}catch (\Exception \$e){\n".
			$indent."\t\t"."throw new \\InvalidArgumentException(\$e->getMessage(), 0, \$e);\n".
			$indent."\t}\n".
			$indent."\t".'return '.$this->innerType->getDeserializer('$decoded', $indent."\t").";\n".
			$indent."})($value_expression)";
	}

	public function getEqualizer(string $exp1, string $exp2, string $indent) : string
	{
		return $this->innerType->getEqualizer($exp1, $exp2, $indent);
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
