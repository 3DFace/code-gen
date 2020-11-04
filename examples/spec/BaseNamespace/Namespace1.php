<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace BaseNamespace\Namespace1;

use BaseNamespace\Namespace2\SomeSibling;
use dface\CodeGen\ClassName;
use dface\CodeGen\DynamicTypeDef;
use dface\CodeGen\EqualsBySerialize;
use dface\CodeGen\JsonType;
use dface\CodeGen\ScalarType;
use dface\CodeGen\TestInterface;
use dface\CodeGen\VirtualType;

return [

	'SomeClass' => [
		'field1' => ['type' => new ScalarType('string'), 'alias' => 'old_field1', 'with'=>true, 'null' => true],
		'field2' => ['type' => SomeSibling::class],
		'field2json' => ['type' => new JsonType(new DynamicTypeDef(new ClassName(SomeSibling::class)), 0 ,0, false), 'default' => null],
		'field3' => ['type' => Value::class, 'default' => null, 'empty' => [], 'merged' => true],
		'field4' => ['type' => 'Value{}'],
		'field5' => ['type' => new VirtualType(\JsonSerializable::class, [
			Value::class => 1,
		]), 'with' => 1],
		'field6' => ['type' => 'virtual[]'],
		'field61' => ['type' => 'DateInterval', 'default' => null, 'silent' => true],
		'field7' => ['type' => 'mixed', 'default' => null],
		'field8' => ['type' => 'DateTime[]', 'default' => null, 'write_as' => ['__field8', '_field8'], 'read_as' => ['__field8', '_field8']],
		'field9' => ['type' => 'TimeStamp{}', 'default' => null],
		'field10' => ['type' => TagType::class, 'default' => null],
		'field11' => ['type' => 'DateTime', 'default' => null],
		'field12' => ['type' => 'string{}', 'default' => null, 'empty' => ['legacy' => 'asd']],
	],

	'Value' => [
		'val' => ['type' => 'string', 'set' => true, 'null' => true, 'empty' => null],
	],

	'TagType' => [
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
