<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

interface TypeDef
{

	/**
	 * TypeDef can specify classes to import (for 'use' section).
	 * @param string $namespace - generated class's namespace
	 * @return string[] class names
	 */
	public function getUses(string $namespace) : array;

	/**
	 * Makes PHP-code that serializes values of target type.
	 * @param string $value_expression - PHP-expression that represents a value being serialized
	 * @param bool $null_able
	 * @param string $indent for code formatting
	 * @return string PHP-code
	 */
	public function getSerializer(string $value_expression, bool $null_able, string $indent) : string;

	/**
	 * Makes PHP-code that deserializes values returned from 'serialize'-method to target type.
	 * @param string $l_value - PHP-expression that represents a value being deserialized
	 * @param string $indent for code formatting
	 * @return string PHP-code
	 */
	public function getDeserializer(string $l_value, string $indent) : string;

	/**
	 * Returns PHP-lang type hinting
	 * @return string
	 */
	public function getArgumentHint() : string;

	/**
	 * Returns PHPDOC type hinting
	 * @return string
	 */
	public function getPhpDocHint() : string;

}
