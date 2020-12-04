<?php

namespace dface\CodeGen;

class DateIntervalType implements TypeDef
{

	public function getUses(string $namespace) : array
	{
		return [\DateInterval::class];
	}

	public function getSerializer(string $value_expression, bool $null_able, string $indent) : string
	{
		return ($null_able ? "$value_expression === null ? null : " : '')."(static function (DateInterval \$x){\n".
			$indent."\t\$str = '';\n".
			$indent."\tif (\$x->y) {\n".
			$indent."\t\t\$str .= \$x->y.'Y';\n".
			$indent."\t}\n".
			$indent."\tif (\$x->m) {\n".
			$indent."\t\t\$str .= \$x->m.'M';\n".
			$indent."\t}\n".
			$indent."\tif (\$x->d) {\n".
			$indent."\t\t\$str .= \$x->d.'D';\n".
			$indent."\t}\n".
			$indent."\tif (\$x->h || \$x->i || \$x->s) {\n".
			$indent."\t\t\$str .= 'T';\n".
			$indent."\t\tif (\$x->h) {\n".
			$indent."\t\t\t\$str .= \$x->h.'H';\n".
			$indent."\t\t}\n".
			$indent."\t\tif (\$x->i) {\n".
			$indent."\t\t\t\$str .= \$x->i.'M';\n".
			$indent."\t\t}\n".
			$indent."\t\tif (\$x->s) {\n".
			$indent."\t\t\t\$str .= \$x->s.'S';\n".
			$indent."\t\t}\n".
			$indent."\t}\n".
			$indent."\treturn 'P'.(\$str ?: 'T0S');\n".
			$indent."})($value_expression)";
	}

	public function getDeserializer(string $l_value, string $indent) : string
	{
		return "if($l_value !== null){\n".
			$indent."\t"."try {\n".
			$indent."\t\t"."$l_value = new DateInterval($l_value);\n".
			$indent."\t}catch (\Exception \$e){\n".
			$indent."\t\t"."throw new \\InvalidArgumentException(\$e->getMessage(), 0, \$e);\n".
			$indent."\t}\n".
			$indent."}\n";
	}

	public function getArgumentHint() : string
	{
		return 'DateInterval';
	}

	public function getPhpDocHint() : string
	{
		return 'DateInterval';
	}

}
