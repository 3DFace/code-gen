<?php

namespace dface\CodeGen;

class DateIntervalType implements TypeDef
{
	public function getUses($namespace)
	{
		return [\DateInterval::class];
	}

	public function getSerializer($value_expression, $null_able, $indent)
	{
		return ($null_able ? "$value_expression === null ? null : " : '')."\call_user_func(function (\\DateInterval \$x){\n".
			$indent."\t\$str = 'P';\n".
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
			$indent."\treturn \$str;\n".
			$indent."}, $value_expression)";
	}

	public function getDeserializer($target, $value_expression, $indent)
	{
		$body = "try {\n";
		$body .= $indent."\t"."$target = $value_expression !== null ? new DateInterval($value_expression) : null;\n";
		$body .= $indent."}catch (\Exception \$e){\n";
		$body .= $indent."\t"."throw new \\InvalidArgumentException(\$e->getMessage(), 0, \$e);\n";
		$body .= $indent."}\n";
		return $body;
	}

	public function getArgumentHint()
	{
		return 'DateInterval';
	}

	public function getPhpDocHint()
	{
		return 'DateInterval';
	}

}
