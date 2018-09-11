<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class TimeStampType implements TypeDef
{

	public function getUses($namespace)
	{
		return [\DateTimeImmutable::class];
	}

	public function getSerializer($value_expression, $null_able, $indent)
	{
		return ($null_able ? "$value_expression === null ? null : " : '').$value_expression.'->getTimestamp()';
	}

	public function getDeserializer($target, $value_expression, $indent)
	{
		$body = "try {\n";
		$body .= $indent."\t"."$target = $value_expression !== null ? (new DateTimeImmutable())->setTimestamp($value_expression) : null;\n";
		$body .= $indent."}catch (\Exception \$e){\n";
		$body .= $indent."\t"."throw new \\InvalidArgumentException(\$e->getMessage(), 0, \$e);\n";
		$body .= $indent."}\n";
		return $body;
	}

	public function getArgumentHint()
	{
		return 'DateTimeImmutable';
	}

	public function getPhpDocHint()
	{
		return 'DateTimeImmutable';
	}

}
