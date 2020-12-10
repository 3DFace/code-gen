<?php

namespace dface\CodeGen;

interface FieldDef
{

	public function makeConstructorFragment(
		string $indent,
		array &$constructor_doc,
		array &$constructor_params
	) : string;

	public function makeGetter(string $indent) : string;

	public function makeSetter(string $indent) : string;

	public function makeWither(string $indent) : string;

	public function makeSerializerFragment(
		string $array_l_value,
		string $indent
	) : string;

	public function makeDeserializerFragment(
		string $indent,
		array &$constructor_args
	) : string;

	public function makeField(
		string $indent,
		string $def_visibility
	) : string;

	public function makeEqualsFragment(
		string $peer_l_value,
		string $indent
	) : string;

	public function makeUses() : iterable;

}
