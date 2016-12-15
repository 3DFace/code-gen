<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace BaseNamespace\Namespace1;

use BaseNamespace\Namespace2\SomeSibling;

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
		'val' => ['type' => 'string'],
	],

	'Virtual1' => [
		'val' => ['type' => 'string'],
	],

	'Virtual2' => [
		'val' => ['type' => 'string'],
	],

];
