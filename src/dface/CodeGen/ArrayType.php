<?php

namespace dface\CodeGen;

class ArrayType implements TypeDef
{

	private TypeDef $inner_type;
	private bool $nullable;

	public function __construct(TypeDef $inner_type, bool $nullable = false)
	{
		$this->inner_type = $inner_type;
		$this->nullable = $nullable;
	}

	public function getUses() : iterable
	{
		return $this->inner_type->getUses();
	}

	public function getSerializer(string $value_expression, string $indent) : string
	{
		if (\is_a($this->inner_type, ScalarType::class)) {
			return $value_expression;
		}
		$type_hint = $this->inner_type->getArgumentHint();
		if ($type_hint) {
			$type_hint .= ' ';
		}
		return ($this->nullable ? "$value_expression === null ? null : " : '')."\\array_map(static function ($type_hint\$x) {\n".
			$indent."\t".'return '.$this->inner_type->getSerializer('$x', $indent."\t").";\n".
			$indent."}, $value_expression)";
	}

	public function getDeserializer(string $value_expression, string $indent) : string
	{
		return "$value_expression === null ? null : \\array_map(static function (\$x) {\n".
			$indent."\t".'return '.$this->inner_type->getDeserializer('$x', $indent."\t").";\n".
			$indent."}, $value_expression)";
	}

	private function notNullEq(string $exp1, string $exp2, string $indent) : string
	{
		return "\count($exp1) === \count($exp2)\n$indent&& (static function (\$arr1, \$arr2) {\n".
			$indent."\t"."foreach (\$arr1 as \$i => \$v1) {\n".
			$indent."\t\t"."if (!isset(\$arr2[\$i])) {\n".
			$indent."\t\t\t"."return false;\n".
			$indent."\t\t"."}\n".
			$indent."\t\t"."\$v2 = \$arr2[\$i];\n".
			$indent."\t\t"."\$v_eq = ".$this->inner_type->getEqualizer('$v1', '$v2', $indent."\t\t").";\n".
			$indent."\t\t"."if (!\$v_eq) {\n".
			$indent."\t\t\t"."return false;\n".
			$indent."\t\t"."}\n".
			$indent."\t"."}\n".
			$indent."\t"."return true;\n".
			$indent."})($exp1, $exp2)";
	}

	public function getEqualizer(string $exp1, string $exp2, string $indent) : string
	{
		if (!$this->nullable) {
			return $this->notNullEq($exp1, $exp2, $indent);
		}
		$eq = $this->notNullEq($exp1, $exp2, $indent."\t\t");
		return "(($exp1 === null && $exp2 === null)\n$indent\t|| ($exp1 !== null && $exp2 !== null\n$indent\t\t&& $eq))";
	}

	public function getArgumentHint() : string
	{
		return ($this->nullable ? '?' : '').'array';
	}

	public function getPhpDocHint() : string
	{
		$inner = $this->inner_type->getPhpDocHint();
		return \str_replace('|', '[]|', $inner).'[]'.($this->nullable ? '|null' : '');
	}

	public function createNullable() : TypeDef
	{
		if ($this->nullable) {
			return $this;
		}
		$x = clone $this;
		$x->nullable = true;
		return $x;
	}

	public function varExport($value, string $indent) : string
	{
		/** @var array $value */
		if ($value === null) {
			return 'null';
		}
		if ($value === []) {
			return Utils::plainVarExport($value, $indent);
		}
		$exported = \array_map(function ($val) use ($indent) {
			return $this->inner_type->varExport($val, $indent."\t");
		}, $value);
		return "[\n$indent\t".\implode(",\n$indent\t", $exported).",\n$indent]";
	}

	/**
	 * @param array|null $value
	 * @return bool
	 */
	public function isDefaultInlineable($value) : bool
	{
		if ($value === null) {
			return true;
		}
		foreach ($value as $item) {
			if (!$this->inner_type->isDefaultInlineable($item)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param null|array $value
	 * @return null|array
	 */
	public function serialize($value) : ?array
	{
		if ($value === null) {
			return null;
		}
		return \array_map(function ($x) {
			return $this->inner_type->serialize($x);
		}, $value);
	}

}
