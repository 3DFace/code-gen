<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

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
];

$gen = new DTOGenerator($specSrc, $writer, $predefinedTypes);

$gen->generate();

// see results in ./examples/classes
