<?php

namespace dface\CodeGen;

class DefaultDef
{

	private string $code;
	private bool $embeddable;

	public function __construct(string $code, bool $embeddable)
	{
		$this->code = $code;
		$this->embeddable = $embeddable;
	}

	public function getCode() : string
	{
		return $this->code;
	}

	public function getValue()
	{
		/** @noinspection PhpUnreachableStatementInspection */
		return eval("return $this->code;");
	}

	public function isEmbeddable() : bool
	{
		return $this->embeddable;
	}

	public static function fromValue($value) : self
	{
		$code = self::varExport($value);
		$embeddable = self::isScalarStruct($value);
		return new self($code, $embeddable);
	}

	private static function isScalarStruct($var) : bool
	{
		if (\is_array($var)) {
			foreach ($var as $x) {
				if (!self::isScalarStruct($x)) {
					return false;
				}
			}
			return true;
		}
		return $var === null || \is_scalar($var);
	}

	private static function varExport($var) : string
	{
		if ($var === null) {
			return 'null';
		}
		if ($var === []) {
			return '[]';
		}
		return \var_export($var, true);
	}

}
