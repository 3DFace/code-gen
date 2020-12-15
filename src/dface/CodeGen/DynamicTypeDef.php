<?php

namespace dface\CodeGen;

class DynamicTypeDef implements TypeDef
{

	private ClassName $class_name;
	private bool $nullable;

	public function __construct(ClassName $data_class_name, bool $nullable = false)
	{
		$this->class_name = $data_class_name;
		$this->nullable = $nullable;
	}

	public function getClassName() : ClassName
	{
		return $this->class_name;
	}

	public function getUses() : array
	{
		return [$this->class_name->getFullName()];
	}

	public function getSerializer(string $value_expression, string $indent) : string
	{
		return ($this->nullable ? "$value_expression === null ? null : " : '').$value_expression.'->jsonSerialize()';
	}

	public function getDeserializer(string $value_expression, string $indent) : string
	{
		return "$value_expression === null ? null : ".$this->class_name->getShortName()."::deserialize($value_expression)";
	}

	public function getEqualizer(string $exp1, string $exp2, string $indent) : string
	{
		if (\method_exists($this->class_name->getFullName(), 'equals')) {
			$not_null = $exp1.'->equals('.$exp2.')';
		} else {
			$not_null = $exp1.' == '.$exp2;
		}
		if (!$this->nullable) {
			return $not_null;
		}
		return "(($exp1 === $exp2)\n$indent\t|| ($exp1 !== null && $exp2 !== null\n$indent\t\t&& $not_null))";
	}

	public function getArgumentHint() : string
	{
		return ($this->nullable ? '?' : '').$this->class_name->getShortName();
	}

	public function getPhpDocHint() : string
	{
		return $this->class_name->getShortName().($this->nullable ? '|null' : '');
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

}
