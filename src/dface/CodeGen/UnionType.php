<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class UnionType implements TypeDef
{

	/** @var array[] */
	private array $types;
	private bool $nullable;

	public function __construct(array $type_to_id_map, bool $nullable = false)
	{
		$this->types = [];
		foreach ($type_to_id_map as $class_name => $id) {
			$this->types[] = [new DynamicTypeDef(new ClassName($class_name), false), $id];
		}
		$this->nullable = $nullable;
	}

	public function getUses() : \Generator
	{
		foreach ($this->types as [$type_def]) {
			/** @var TypeDef $type_def */
			yield from $type_def->getUses();
		}
	}

	public function getSerializer(string $value_expression, string $indent) : string
	{
		$result = ($this->nullable ? "$value_expression === null ? null : " : '')."(static function (\$val) {\n";

		foreach ($this->types as [$type_def, $id]) {
			/** @var DynamicTypeDef $type_def */
			$short = $type_def->getClassName()->getShortName();
			$id_ex = \var_export($id, true);
			$inner_serializer = $type_def->getSerializer('$val', $indent."\t\t\t");
			$result .=
				$indent."\t"."if (\$val instanceof $short) {\n".
				$indent."\t\t"."return [$id_ex, $inner_serializer];\n".
				$indent."\t"."}\n";
		}
		$result .=
			$indent."\t"."throw new \\RuntimeException('Unsupported union type '.\gettype(\$val));\n".
			$indent."})($value_expression)";
		return $result;
	}

	public function getDeserializer(string $value_expression, string $indent) : string
	{
		$result = "$value_expression === null ? null : (static function (\$x) {\n".
			$indent."\t"."if (!\\is_array(\$x)) {\n".
			$indent."\t\t"."throw new \\InvalidArgumentException('Cant deserialize '.\gettype(\$x));\n".
			$indent."\t"."}\n";
		if (\count($this->types) === 1) {
			/** @var DynamicTypeDef $type_def */
			[$type_def, $id] = $this->types[0];
			$inner_deserializer = $type_def->getDeserializer('$x[1]', $indent."\t\t\t");
			$id_ex = \var_export($id, true);
			$result .= $indent."\t"."if(\$x"."[0] === $id_ex){\n".
				$indent."\t\t"."return $inner_deserializer;\n".
				$indent."\t"."}\n".
				$indent."\t"."throw new \\InvalidArgumentException('Unknown type id: '.\$x"."[0]);\n";
		} else {
			$result .= $indent."\t"."switch (\$x"."[0]) {\n";
			foreach ($this->types as [$type_def, $id]) {
				/** @var DynamicTypeDef $type_def */
				$inner_deserializer = $type_def->getDeserializer('$x[1]', $indent."\t\t\t");
				$id_ex = \var_export($id, true);
				$result .=
					$indent."\t\t"."case $id_ex:\n".
					$indent."\t\t\t"."return $inner_deserializer;\n";
			}
			$result .=
				$indent."\t\t"."default:\n".
				$indent."\t\t\t"."throw new \\InvalidArgumentException('Unknown type id: '.\$x"."[0]);\n".
				$indent."\t"."}\n";
		}
		$result .= $indent."})($value_expression)";
		return $result;
	}

	public function getEqualizer(string $exp1, string $exp2, string $indent) : string
	{
		$not_null = $exp1.'->equals('.$exp2.')';
		if (!$this->nullable) {
			return $not_null;
		}
		return "(($exp1 === null && $exp2 === null)\n$indent\t|| ($exp1 !== null && $exp2 !== null\n$indent\t\t&& $not_null))";
	}

	public function getArgumentHint() : string
	{
		if (\count($this->types) === 1) {
			/** @var DynamicTypeDef $type_def */
			[$type_def] = $this->types[0];
			return ($this->nullable ? '?' : '').$type_def->getArgumentHint();
		}
		return '';
	}

	public function getPhpDocHint() : string
	{
		$hints = [];
		foreach ($this->types as [$type_def]) {
			/** @var DynamicTypeDef $type_def */
			$hints[] = $type_def->getPhpDocHint();
		}
		return \implode('|', $hints).($this->nullable ? '|null' : '');
	}

	public function createNullable() : TypeDef
	{
		$x = clone $this;
		$x->nullable = true;
		return $x;
	}

}
