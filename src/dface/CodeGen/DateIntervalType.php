<?php

namespace dface\CodeGen;

class DateIntervalType implements TypeDef
{

	private bool $nullable;

	public function __construct(bool $nullable = false)
	{
		$this->nullable = $nullable;
	}

	public function getUses() : array
	{
		return [\DateInterval::class];
	}

	public function getSerializer(string $value_expression, string $indent) : string
	{
		return ($this->nullable ? "$value_expression === null ? null : " : '')."(static function (DateInterval \$x) {\n".
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

	public function getDeserializer(string $value_expression, string $indent) : string
	{
		return "$value_expression === null ? null : (static function (\$x) {\n".
			$indent."\t"."try {\n".
			$indent."\t\t"."return new DateInterval(\$x);\n".
			$indent."\t} catch (\Exception \$e) {\n".
			$indent."\t\t"."throw new \\InvalidArgumentException(\$e->getMessage(), 0, \$e);\n".
			$indent."\t}\n".
			$indent."})($value_expression)";
	}

	public function getEqualizer(string $exp1, string $exp2, string $indent) : string
	{
		$pairs = [];
		foreach (['y', 'm', 'd', 'h', 'i', 's'] as $k) {
			$pairs[] = '('.$exp1."->$k === ".$exp2."->$k".')';
		}
		if (!$this->nullable) {
			return \implode("\n$indent && ", $pairs);
		}
		$not_null_str = \implode("\n\t\t$indent&& ", $pairs);
		return "(($exp1 === $exp2)\n$indent\t|| ($exp1 !== null && $exp2 !== null\n$indent\t\t&& $not_null_str))";
	}

	public function getArgumentHint() : string
	{
		return ($this->nullable ? '?' : '').'DateInterval';
	}

	public function getPhpDocHint() : string
	{
		return 'DateInterval'.($this->nullable ? '|null' : '');
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
		/** @var $value \DateInterval */
		$str = self::intervalToString($value);
		$exported = Utils::varExport($str, $indent);
		return "new DateInterval($exported)";
	}

	private static function intervalToString(\DateInterval $x) : string
	{
		$str = '';
		if ($x->y) {
			$str .= $x->y.'Y';
		}
		if ($x->m) {
			$str .= $x->m.'M';
		}
		if ($x->d) {
			$str .= $x->d.'D';
		}
		if ($x->h || $x->i || $x->s) {
			$str .= 'T';
			if ($x->h) {
				$str .= $x->h.'H';
			}
			if ($x->i) {
				$str .= $x->i.'M';
			}
			if ($x->s) {
				$str .= $x->s.'S';
			}
		}
		return 'P'.($str ?: 'T0S');
	}

	public function isDefaultInlineable($value) : bool
	{
		return $value === null;
	}

	/**
	 * @param null|\DateInterval $value
	 * @return null|string
	 */
	public function serialize($value) : ?string
	{
		if ($value === null) {
			return null;
		}
		return self::intervalToString($value);
	}

}
