<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

interface TypeDef
{

	/**
	 * TypeDef can specify classes to import (for 'use' section).
	 * @return string[]|iterable class names
	 */
	public function getUses() : iterable;

	/**
	 * Makes PHP-code that serializes values of target type.
	 * @param string $value_expression - PHP-expression that represents a value being serialized
	 * @param string $indent for code formatting
	 * @return string PHP-code
	 */
	public function getSerializer(string $value_expression, string $indent) : string;

	/**
	 * Makes PHP-code that deserializes values returned from 'serialize'-method to target type.
	 * @param string $value_expression - PHP-expression that represents a value being deserialized
	 * @param string $indent for code formatting
	 * @return string PHP-code
	 */
	public function getDeserializer(string $value_expression, string $indent) : string;

	/**
	 * Builds PHP-code that makes values equality check.
	 * @param string $exp1
	 * @param string $exp2
	 * @param string $indent
	 * @return string
	 */
	public function getEqualizer(string $exp1, string $exp2, string $indent) : string;

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

	public function createNullable() : self;

}
