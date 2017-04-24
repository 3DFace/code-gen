<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace BaseNamespace\Namespace1;

use BaseNamespace\Namespace2\SomeSibling;
use dface\CodeGen\EqualsBySerialize;
use dface\CodeGen\ScalarType;
use dface\CodeGen\TestInterface;
use dface\CodeGen\VirtualType;

return [

	'SomeClass' => [
		'field1' => ['type' => new ScalarType('string'), 'alias' => 'old_field1', 'with'=>true, 'null' => true],
		'field2' => ['type' => SomeSibling::class],
		'field3' => ['type' => Value::class, 'default' => null, 'empty' => [], 'merged' => true],
		'field4' => ['type' => 'Value{}'],
		'field5' => ['type' => new VirtualType(\JsonSerializable::class, [
			Value::class => 1,
		]), 'with' => 1],
		'field6' => ['type' => 'virtual[]'],
		'field7' => ['type' => 'mixed', 'default' => null],
	],

	'Value' => [
		'val' => ['type' => 'string', 'set' => true, 'null' => true, 'empty' => null],
	],

	'Virtual1' => [
		'val' => ['type' => 'string'],
		'test' => ['type' => 'string'],
		'@implements' => TestInterface::class,
		'@traits' => EqualsBySerialize::class,
	],

	'Virtual2' => [
		'val' => ['type' => 'string'],
	],

];
