<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace BaseNamespace\Namespace1;

use BaseNamespace\Namespace2\SomeSibling;
use dface\CodeGen\EqualsBySerialize;
use dface\CodeGen\TestInterface;

return [

	'SomeClass' => [
		'field1' => ['type' => 'string', 'alias' => 'old_field1', 'with'=>true],
		'field2' => ['type' => SomeSibling::class],
		'field3' => ['type' => Value::class, 'default' => null],
		'field4' => ['type' => 'Value{}'],
		'field5' => ['type' => 'virtual'],
		'field6' => ['type' => 'virtual[]'],
	],

	'Value' => [
		'val' => ['type' => 'string', 'set' => true],
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
