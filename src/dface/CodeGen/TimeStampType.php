<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class TimeStampType implements TypeDef
{

	public function getUses(string $namespace) : array
	{
		return [\DateTimeImmutable::class];
	}

	public function getSerializer(string $value_expression, bool $null_able, string $indent) : string
	{
		return ($null_able ? "$value_expression === null ? null : " : '').$value_expression.'->getTimestamp()';
	}

	public function getDeserializer(string $l_value, string $indent) : string
	{
		return "if($l_value !== null){\n".
			$indent."\t"."try {\n".
			$indent."\t\t"."$l_value = (new DateTimeImmutable())->setTimestamp($l_value);\n".
			$indent."\t}catch (\Exception \$e){\n".
			$indent."\t\t"."throw new \\InvalidArgumentException(\$e->getMessage(), 0, \$e);\n".
			$indent."\t}\n".
			$indent."}\n";
	}

	public function getArgumentHint() : string
	{
		return 'DateTimeImmutable';
	}

	public function getPhpDocHint() : string
	{
		return 'DateTimeImmutable';
	}

}
