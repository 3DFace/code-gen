<?php

namespace BaseNamespace\Namespace1;

use BaseNamespace\Namespace2\SomeSibling;
use dface\CodeGen\ClassName;
use dface\CodeGen\DynamicTypeDef;
use dface\CodeGen\JsonType;
use dface\CodeGen\ScalarType;
use dface\CodeGen\TestInterface;
use dface\CodeGen\UnionType;

return [

	'SomeClass' => [
		'field1' => ['type' => new ScalarType('string', true), 'alias' => 'old_field1', 'with'=>true, 'null' => true],
		'field2' => ['type' => SomeSibling::class],
		'field2json' => ['type' => new JsonType(new DynamicTypeDef(new ClassName(SomeSibling::class), true), 0 ,0, false), 'default' => null],
		'field3' => ['type' => Value::class, 'default' => new Value('Viva'), 'empty' => 'none', 'silent' => true, 'null' => true],
		'field4' => ['type' => Value::class.'{}', 'set' => true],
		'field41' => ['type' => Value::class, 'merged' => true],
		'field5' => ['type' => new UnionType([
			Value::class => 1,
		]), 'with' => 1],
		'field50' => '?string',
		'field51' => ['type' => 'union', 'with' => true],
		'field6' => ['type' => 'union[]', 'default' => []],
		'field61' => ['type' => 'DateInterval', 'default' => null, 'silent' => true],
		'field7' => ['type' => 'mixed', 'default' => null],
		'field8' => ['type' => 'DateTime[]', 'default' => null,
			'silent' => true,
			'write_as' => ['__field8', '_field8'], 'read_as' => ['__field8', '_field8']],
		'field9' => ['type' => 'TimeStamp{}', 'default' => null],
		'field10' => ['type' => TagType::class, 'default' => null],
		'field11' => ['type' => 'DateTime', 'default' => null],
		'field12' => ['type' => 'string{}', 'default' => ['default' => 'zxc'], 'empty' => ['legacy' => 'asd']],
	],

	'Value' => [
		'val' => ['type' => '?string', 'get' => false, 'empty' => null, 'field_visibility' => 'public'],
	],

	'TagType' => [
	],

	'Union1' => [
		'val' => ['type' => 'string'],
		'test' => ['type' => 'string'],
		'@implements' => TestInterface::class,
	],

	'Union2' => [
		'val' => ['type' => 'string'],
	],

];
