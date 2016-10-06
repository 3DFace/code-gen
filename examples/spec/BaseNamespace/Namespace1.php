<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

return [

	'SomeClass' => [
		'field1' => ['type' => 'string', 'alias' => 'old_field1', 'with'=>true],
		'field2' => ['type' => '\BaseNamespace\Namespace2\SomeSibling', 'default' => 'asd'],
		'field3' => ['type' => 'Value', 'default' => null],
		'field4' => ['type' => 'Value[]'],
	],

	'Value' => [
		'val' => ['type' => 'string'],
	],

];
