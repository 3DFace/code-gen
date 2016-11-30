<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

return [

	'SomeClass' => [
		'field1' => ['type' => 'string', 'alias' => 'old_field1', 'with'=>true],
		'field2' => ['type' => '\BaseNamespace\Namespace2\SomeSibling'],
		'field3' => ['type' => 'Value', 'default' => null],
		'field4' => ['type' => 'Value[]'],
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
