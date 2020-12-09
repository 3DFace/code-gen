<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class DynamicTypeDef implements TypeDef
{

	private ClassName $className;
	private bool $nullable;

	public function __construct(ClassName $dataClassName, bool $nullable = false)
	{
		$this->className = $dataClassName;
		$this->nullable = $nullable;
	}

	public function getClassName() : ClassName
	{
		return $this->className;
	}

	public function getUses(string $namespace) : array
	{
		return $namespace !== $this->className->getNamespace() ? [$this->className->getFullName()] : [];
	}

	public function getSerializer(string $value_expression, string $indent) : string
	{
		return ($this->nullable ? "$value_expression === null ? null : " : '').$value_expression.'->jsonSerialize()';
	}

	public function getDeserializer(string $value_expression, string $indent) : string
	{
		return "$value_expression === null ? null : ".$this->className->getShortName()."::deserialize($value_expression)";
	}

	public function getEqualizer(string $exp1, string $exp2, string $indent) : string
	{
		if (\method_exists($this->className->getFullName(), 'equals')) {
			$not_null = $exp1.'->equals('.$exp2.')';
		} else {
			$not_null = $exp1.' == '.$exp2;
		}
		if (!$this->nullable) {
			return $not_null;
		}
		return "(($exp1 === null && $exp2 === null)\n$indent\t|| ($exp1 !== null && $exp2 !== null\n$indent\t\t&& $not_null))";
	}

	public function getArgumentHint() : string
	{
		return ($this->nullable ? '?' : '').$this->className->getShortName();
	}

	public function getPhpDocHint() : string
	{
		return $this->className->getShortName().($this->nullable ? '|null' : '');
	}

	public function createNullable() : TypeDef
	{
		$x = clone $this;
		$x->nullable = true;
		return $x;
	}

}
