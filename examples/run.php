<?php

/**
 * @noinspection PhpUnhandledExceptionInspection
 * @noinspection PhpComposerExtensionStubsInspection
 * @noinspection ForgottenDebugOutputInspection
 */
namespace dface\CodeGen;

use BaseNamespace\Namespace1\SomeClass;
use BaseNamespace\Namespace1\Value;
use BaseNamespace\Namespace1\Virtual1;
use BaseNamespace\Namespace1\Virtual2;
use BaseNamespace\Namespace2\SomeSibling;

include_once __DIR__.'/../vendor/autoload.php';

// source
$specSrc = new PhpFilesSpecSource('', __DIR__.'/spec');

// destination - examples/classes
$writer = new Psr0ClassWriter(__DIR__.'/classes');

$predefinedTypes = [
	'string' => new ScalarType('string'),
	'int' => new ScalarType('int'),
	'float' => new ScalarType('float'),
	'bool' => new ScalarType('bool'),
	'mixed' => new MixedType(),
	'DateTime' => new DateTimeType('Y-m-d H:i:s'),
	'TimeStamp' => new TimeStampType(),
	'DateInterval' => new DateIntervalType(),
	'virtual' => new VirtualType(\JsonSerializable::class, [
		Virtual1::class => 'v1',
		Virtual2::class => 'v2',
	]),
];

$gen = new DTOGenerator($specSrc, $writer, $predefinedTypes);

$gen->generate();

$x = new SomeClass(
	'asd',
	new SomeSibling('zxc'),
	new SomeSibling('asd'),
	new Value('qwe'),
	['a' => new Value('1'), 'b' => new Value('2'), 's' => new Value('3')],
	new Value(2),
	[new Virtual1('qaz', 'gaga'), new Virtual2('qaz')],
	new \DateInterval('P1M1DT10H'));

$s = $x->jsonSerialize();


echo \json_encode($s, JSON_THROW_ON_ERROR)."\n";

$x = SomeClass::deserialize($s);

\var_dump($x);

// see results in ./examples/classes
