<?php

/**
 * @noinspection PhpUnhandledExceptionInspection
 * @noinspection PhpComposerExtensionStubsInspection
 * @noinspection ForgottenDebugOutputInspection
 */
namespace dface\CodeGen;

use BaseNamespace\Namespace1\SomeClass;
use BaseNamespace\Namespace1\Union1;
use BaseNamespace\Namespace1\Union2;
use BaseNamespace\Namespace1\Value;
use BaseNamespace\Namespace2\SomeSibling;

include_once __DIR__.'/../vendor/autoload.php';

$predefinedTypes = [
	'string' => new ScalarType('string'),
	'int' => new ScalarType('int'),
	'float' => new ScalarType('float'),
	'bool' => new ScalarType('bool'),
	'mixed' => new MixedType(),
	'DateTime' => new DateTimeType('Y-m-d H:i:s'),
	'TimeStamp' => new TimeStampType(),
	'DateInterval' => new DateIntervalType(),
	'union' => new UnionType([
		Union1::class => 'v1',
		Union2::class => 'v2',
	], true),
];

// source
$specSrc = new PhpFilesSpecSource($predefinedTypes, '', __DIR__.'/spec');
// destination - examples/classes
$writer = new Psr0ClassWriter(__DIR__.'/classes');

$gen = new DTOGenerator($specSrc, $writer);

$gen->generate();

$x1 = new SomeClass(
	'asd',
	new SomeSibling('zxc'),
	new SomeSibling('asd'),
	new Value('qwe'),
	['a' => new Value('1'), 'b' => new Value('2'), 's' => new Value('3')],
	new Value('x'),
	new Value(2),
	'nullable',
	null,
	TestEnum::second,
	[new Union1('qaz', 'gaga'), new Union2('qaz')],
	new \DateInterval('P1M1DT10H'));

$s1 = \json_encode($x1->jsonSerialize(), JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
echo $s1."\n";

$x2 = SomeClass::deserialize(\json_decode($s1, false, 512, JSON_THROW_ON_ERROR));
$s2 = \json_encode($x2->jsonSerialize(), JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
echo  $s2."\n";

\var_dump($x2);

if(!$x1->equals($x2)){
	echo "\n\n obj mismatch!\n";
}
if($s2 !== $s1){
	echo "\n\n str mismatch!\n";
}

// see results in ./examples/classes
