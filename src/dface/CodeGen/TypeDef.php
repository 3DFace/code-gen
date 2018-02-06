<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

interface TypeDef {

	/**
	 * TypeDef can specify classes to import (for 'use' section).
	 * @param string $namespace - generated class's namespace
	 * @return string[] class names
	 */
	function getUses($namespace);

	/**
	 * Makes PHP-code that serializes values of target type.
	 * @param string $value_expression - PHP-expression that represents a value being serialized
	 * @param string $indent for code formatting
	 * @return string PHP-code
	 */
	function getSerializer($value_expression, $indent);

	/**
	 * Makes PHP-code that deserializes values returned from 'serialize'-method to target type.
	 * @param $l_value
	 * @param string $value_expression - PHP-expression that represents a value being deserialized
	 * @param string $indent for code formatting
	 * @return string PHP-code
	 */
	function getDeserializer($l_value, $value_expression, $indent);

	/**
	 * Returns PHP-lang type hinting
	 * @return string
	 */
	function getArgumentHint();

	/**
	 * Returns PHPDOC type hinting
	 * @return string
	 */
	function getPhpDocHint();

}
