<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

interface TypeDef {

	/**
	 * TypeDef can specify classes to import (for 'use' section).
	 * @param $namespace - generated class's namespace
	 * @return string[] class names
	 */
	function getUses(string $namespace);

	/**
	 * Makes PHP-code that serializes values of target type.
	 * @param string $value_expression - PHP-expression that represents a value being serialized
	 * @return string PHP-code
	 */
	function getSerializer(string $value_expression);

	/**
	 * Makes PHP-code that deserializes values returned from 'serialize'-method to target type.
	 * @param string $value_expression - PHP-expression that represents a value being deserialized
	 * @return string PHP-code
	 */
	function getDeserializer(string $value_expression);

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
