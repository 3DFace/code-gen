<?php

namespace ForeignNamespace;

use dface\CodeGen\ScalarType;

return [

	'Stranger1' => [
		'val' => ['type' => new ScalarType('int', false)],
        '@deprecated' => true,
	],

];
