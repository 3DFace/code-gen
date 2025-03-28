<?php

namespace dface\CodeGen;

class BackedEnumTypeDef implements TypeDef
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
		return $value_expression.'->value';
	}

	public function getDeserializer(string $value_expression, string $indent) : string
	{
		return "$value_expression === null ? null : ".$this->class_name->getShortName()."::from($value_expression)";
	}

	public function getEqualizer(string $exp1, string $exp2, string $indent) : string
	{
		return $exp1.' === '.$exp2;
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

	public function varExport($value, string $indent) : string
	{
		if ($value === null) {
			return 'null';
		}
		return \str_replace('\\'.$this->class_name->getFullName(), $this->class_name->getShortName(), \var_export($value, true));
	}

	public function isDefaultInlineable($value) : bool
	{
		return true;
	}

	/**
	 * @param null|\BackedEnum $value
	 * @return mixed
	 */
	public function serialize($value)
	{
		if ($value === null) {
			return null;
		}
		return  $value->value;
	}

}
