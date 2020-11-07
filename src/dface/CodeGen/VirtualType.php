<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class VirtualType implements TypeDef
{

	private ClassName $baseType;
	/** @var array[] */
	private array $types;

	public function __construct(string $baseType, array $typeToIdMap)
	{
		$this->baseType = new ClassName($baseType);
		$this->types = [];
		foreach ($typeToIdMap as $className => $id) {
			$this->types[] = [new ClassName($className), $id];
		}
	}

	public function getUses(string $namespace) : array
	{
		$uses = [];
		if ($this->baseType->getNamespace() !== $namespace) {
			$uses[$this->baseType->getFullName()] = 1;
		}
		foreach ($this->types as [$class]) {
			/** @var ClassName $class */
			if ($class->getNamespace() !== $namespace) {
				$uses[$class->getFullName()] = 1;
			}
		}
		return \array_keys($uses);
	}

	public function getSerializer(string $value_expression, bool $null_able, string $indent) : string
	{
		$result = ($null_able ? "$value_expression === null ? null : " : '')."(static function (\$val){\n";

		foreach ($this->types as [$class, $id]) {
			/** @var ClassName $class */
			$short = $class->getShortName();
			$id_ex = \var_export($id, true);
			$result .=
				$indent."\t"."if(\$val instanceof $short){\n".
				$indent."\t\t"."return [$id_ex, \$val->jsonSerialize()];\n".
				$indent."\t"."}\n";
		}
		$result .=
			$indent."\t"."throw new \\RuntimeException('Unsupported virtual type '.\gettype(\$val));\n".
			$indent."})($value_expression)";
		return $result;
	}

	public function getDeserializer(string $l_value, string $indent) : string
	{
		$result = "if($l_value !== null){\n".
			$indent."\t"."if(!\\is_array($l_value)){\n".
			$indent."\t\t"."throw new \\InvalidArgumentException('Cant deserialize '.\gettype($l_value));\n".
			$indent."\t"."}\n";
		if (\count($this->types) === 1) {
			[$class, $id] = $this->types[0];
			/** @var ClassName $class */
			$short = $class->getShortName();
			$id_ex = \var_export($id, true);
			$result .= $indent."\t"."if($l_value"."[0] === $id_ex){\n".
				$indent."\t\t"."$l_value = $short::deserialize($l_value"."[1]);\n".
				$indent."\t"."} else {\n".
				$indent."\t\t"."throw new \\InvalidArgumentException('Unknown type id: '.$l_value"."[0]);\n".
				$indent."\t"."}\n";
		} else {
			$result .= $indent."\t"."switch($l_value"."[0]){\n";
			foreach ($this->types as [$class, $id]) {
				/** @var ClassName $class */
				$short = $class->getShortName();
				$id_ex = \var_export($id, true);
				$result .=
					$indent."\t\t"."case $id_ex:\n".
					$indent."\t\t\t"."$l_value = $short::deserialize($l_value"."[1]);\n".
					$indent."\t\t\t"."break;\n";
			}
			$result .=
				$indent."\t\t"."default:\n".
				$indent."\t\t\t"."throw new \\InvalidArgumentException('Unknown type id: '.$l_value"."[0]);\n".
				$indent."\t"."}\n";
		}
		$result .=
			$indent."}\n";
		return $result;
	}

	public function getArgumentHint() : string
	{
		if (\count($this->types) === 1) {
			return $this->getPhpDocHint();
		}
		return $this->baseType->getShortName();
	}

	public function getPhpDocHint() : string
	{
		$shorts = [];
		foreach ($this->types as [$class]) {
			/** @var ClassName $class */
			$shorts[] = $class->getShortName();
		}
		return \implode('|', $shorts);
	}

}
