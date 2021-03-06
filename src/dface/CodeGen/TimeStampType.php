<?php

namespace dface\CodeGen;

class TimeStampType implements TypeDef
{

	private bool $nullable;

	public function __construct(bool $nullable = false)
	{
		$this->nullable = $nullable;
	}

	public function getUses() : array
	{
		return [\DateTimeImmutable::class];
	}

	public function getSerializer(string $value_expression, string $indent) : string
	{
		return ($this->nullable ? "$value_expression === null ? null : " : '').$value_expression.'->getTimestamp()';
	}

	public function getDeserializer(string $value_expression, string $indent) : string
	{
		return "$value_expression === null ? null : (static function (\$x) {\n".
			$indent."\t"."try {\n".
			$indent."\t\t"."return (new DateTimeImmutable())->setTimestamp(\$x);\n".
			$indent."\t} catch (\Exception \$e) {\n".
			$indent."\t\t"."throw new \\InvalidArgumentException(\$e->getMessage(), 0, \$e);\n".
			$indent."\t}\n".
			$indent."})($value_expression)";
	}

	public function getEqualizer(string $exp1, string $exp2, string $indent) : string
	{
		$not_null = $exp1.'->getTimestamp() === '.$exp2.'->getTimestamp()';
		if (!$this->nullable) {
			return $not_null;
		}
		return "(($exp1 === $exp2)\n".
			"$indent\t|| ($exp1 !== null && $exp2 !== null\n$indent\t\t&& $not_null))";
	}

	public function getArgumentHint() : string
	{
		return ($this->nullable ? '?' : '').'DateTimeImmutable';
	}

	public function getPhpDocHint() : string
	{
		return 'DateTimeImmutable'.($this->nullable ? '|null' : '');
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
