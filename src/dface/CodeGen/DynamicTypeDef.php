<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class DynamicTypeDef implements TypeDef
{

	private ClassName $className;

	public function __construct(ClassName $dataClassName)
	{
		$this->className = $dataClassName;
	}

	public function getUses(string $namespace) : array
	{
		return $namespace !== $this->className->getNamespace() ? [$this->className->getFullName()] : [];
	}

	public function getSerializer(string $value_expression, bool $null_able, string $indent) : string
	{
		return ($null_able ? "$value_expression === null ? null : " : '').$value_expression.'->jsonSerialize()';
	}

	public function getDeserializer(string $l_value, string $indent) : string
	{
		return "if($l_value !== null){\n".
			$indent."\t"."try {\n".
			$indent."\t\t$l_value = ".$this->className->getShortName()."::deserialize($l_value);\n".
			$indent."\t}catch (\Exception \$e){\n".
			$indent."\t\t"."throw new \InvalidArgumentException('Deserialization error: '.\$e->getMessage(), 0, \$e);\n".
			$indent."\t}\n".
			$indent."}\n";
	}

	public function getArgumentHint() : string
	{
		return $this->className->getShortName();
	}

	public function getPhpDocHint() : string
	{
		return $this->className->getShortName();
	}

}
