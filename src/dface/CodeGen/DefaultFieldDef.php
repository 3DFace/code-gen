<?php

namespace dface\CodeGen;

class DefaultFieldDef implements FieldDef
{

	private string $name;
	private TypeDef $type;
	private ?DefaultDef $constructor_default;
	private ?DefaultDef $serialized_default;
	private bool $wither;
	private bool $setter;
	private bool $getter;
	/** @var string[] */
	private array $read_as;
	/** @var string[] */
	private array $write_as;
	private bool $merged;
	private bool $silent;
	private ?string $field_visibility;

	public function __construct(
		string $name,
		TypeDef $type,
		array $read_as,
		array $write_as,
		?DefaultDef $constructor_default,
		?DefaultDef $serialized_default,
		bool $wither,
		bool $setter,
		bool $getter,
		bool $merged,
		bool $silent,
		?string $field_visibility
	) {
		$this->name = $name;
		$this->type = $type;
		$this->read_as = $read_as;
		$this->write_as = $write_as;
		$this->constructor_default = $constructor_default;
		$this->serialized_default = $serialized_default ?? $constructor_default;
		$this->wither = $wither;
		$this->setter = $setter;
		$this->getter = $getter;
		$this->merged = $merged;
		$this->silent = $silent;
		$visibilitySet = ['private', 'protected', 'public', null];
		if (!\in_array($field_visibility, $visibilitySet, true)) {
			throw new \InvalidArgumentException('Fields visibility must be one of ['.implode(', ', $visibilitySet).']');
		}
		$this->field_visibility = $field_visibility;
	}

	public function makeConstructorFragment(
		string $indent,
		array &$constructor_doc,
		array &$constructor_params
	) : string {
		$property_name = $this->name;

		$arg_type = $this->type;
		$property_info = '';
		$default = $this->constructor_default;
		$inline_def = '';
		$right_val = "\$$property_name";
		if ($default !== null) {
			if ($default->isEmbeddable()) {
				$def_code = \str_replace("\n", "\n".$indent, $default->getCode());
				$inline_def = ' = '.$def_code;
			} else {
				$inline_def = ' = '.'null';
				$arg_type = $arg_type->createNullable();
				$def_code = $default->getCode();
				$property_info = ' <code>?? '.\str_replace("\n", " ", $def_code).'</code>';
				$right_val = "$right_val ?? ".\str_replace("\n", "\n".$indent, $def_code);
			}
		}
		$doc_hint = $arg_type->getPhpDocHint();
		$type_hint = $arg_type->getArgumentHint();
		if ($type_hint) {
			$type_hint .= ' ';
		}
		$constructor_doc[] = "@param $doc_hint \$$property_name$property_info";
		$constructor_params[] = $type_hint.'$'.$property_name.$inline_def;
		return $indent."\$this->$property_name = $right_val;\n";
	}

	public function makeGetter(string $indent) : string
	{
		$property_name = $this->name;
		$body = '';
		if ($this->getter) {
			$doc_hint = $this->type->getPhpDocHint();
			$type_hint = $this->type->getArgumentHint();
			$body .= $indent."/**\n";
			$body .= $indent." * @return $doc_hint\n";
			$body .= $indent." */\n";
			$ret_hint = $type_hint ? (' : '.$type_hint.' ') : '';
			$body .= $indent.'public function get'.Utils::camelCase($property_name)."()$ret_hint{\n";
			$body .= $indent."\t"."return \$this->$property_name;\n";
			$body .= $indent."}\n\n";
		}
		return $body;
	}

	public function makeSetter(string $indent) : string
	{
		$property_name = $this->name;
		$body = '';
		if ($this->setter) {
			$type_hint = $this->type->getArgumentHint();
			$doc_hint = $this->type->getPhpDocHint();
			$type_hint .= $type_hint !== '' ? ' ' : '';
			$body .= $indent."/**\n";
			$body .= $indent." * @param $doc_hint \$val\n";
			$body .= $indent." * @return static\n";
			$body .= $indent." */\n";
			$body .= $indent.'public function set'.Utils::camelCase($property_name)."($type_hint\$val) : self {\n";
			$body .= $indent."\t\$this->$property_name = \$val;\n";
			$body .= $indent."\t\$this->_dirty = true;\n";
			$body .= $indent."\t"."return \$this;\n";
			$body .= $indent."}\n\n";
		}
		return $body;
	}

	public function makeWither(string $indent) : string
	{
		$property_name = $this->name;
		$body = '';
		if ($this->wither) {
			$doc_hint = $this->type->getPhpDocHint();
			$type_hint = $this->type->getArgumentHint();
			$type_hint .= $type_hint !== '' ? ' ' : '';
			$body .= $indent."/**\n";
			$body .= $indent." * @param $doc_hint \$val\n";
			$body .= $indent." * @return static\n";
			$body .= $indent." */\n";
			$body .= $indent.'public function with'.Utils::camelCase($property_name)."($type_hint\$val) : self {\n";
			$body .= $indent."\t\$clone = clone \$this;\n";
			$body .= $indent."\t\$clone->$property_name = \$val;\n";
			$body .= $indent."\t\$clone->_dirty = true;\n";
			$body .= $indent."\t"."return \$clone;\n";
			$body .= $indent."}\n\n";
		}
		return $body;
	}

	public function makeSerializerFragment(string $array_l_value, string $indent) : string
	{
		$property_name = $this->name;
		$prop = '$this->'.$property_name;
		$s_indent = '';
		$body = '';
		if ($this->silent) {
			$s_indent = "\t";
			$def = $this->serialized_default;
			if ($def === null) {
				throw new \InvalidArgumentException("Field '$property_name' need 'empty' or 'default' definition to be 'silent'");
			}
			$def_exp = $def->getCode();
			$body .= $indent."if ($prop !== $def_exp) {\n";
		}
		if ($this->merged) {
			$merge_name = '$merge_'.$property_name;
			$body .= $indent.$s_indent."$merge_name = ".$this->type->getSerializer($prop, $indent.$s_indent).";\n";
			$body .= $indent.$s_indent."if ($merge_name) {\n";
			$body .= $indent.$s_indent."\t"."foreach ($merge_name as \$k => \$v) {\n";
			$body .= $indent.$s_indent."\t\t".$array_l_value.'[$k] = $v;'."\n";
			$body .= $indent.$s_indent."\t}\n";
			$body .= $indent.$s_indent."}\n";
		} else {
			foreach ($this->write_as as $target_name) {
				$target = $array_l_value."['$target_name']";
				$body .= $indent.$s_indent.$target.' = '.$this->type->getSerializer($prop, $indent.$s_indent).";\n";
			}
		}
		if ($this->silent) {
			$body .= $indent."}\n";
		}
		$body .= "\n";
		return $body;
	}

	public function makeDeserializerFragment(string $indent, array &$constructor_args) : string
	{
		$body = '';
		$property_name = $this->name;
		$constructor_args[] = '$'.$property_name;
		if ($this->merged) {
			$body .= $indent."\$$property_name = \$arr;\n";
		} else {
			$default = $this->serialized_default;
			$read_as = $this->read_as;
			if ($default !== null && $default->getCode() === 'null' && \count($read_as) === 1) {
				$alias = $read_as[0];
				$body .= $indent."\$$property_name = \$arr['$alias'] ?? null;\n";
			} else {
				if ($default !== null) {
					$exported_ser_def = \str_replace("\n", "\n".$indent, $default->getCode());
					$body .= $indent."\$$property_name = ".$exported_ser_def.";\n";
				}
				$first = true;
				foreach ($read_as as $alias) {
					if ($first) {
						$body .= $indent."if (\\array_key_exists('$alias', \$arr)) {\n";
						$first = false;
					} else {
						$body .= $indent."} elseif (\\array_key_exists('$alias', \$arr)) {\n";
					}
					$body .= $indent."\t\$$property_name = \$arr['$alias'];\n";
				}
				if ($default === null) {
					$body .= $indent."} else {\n";
					$body .= $indent."\t"."throw new \\InvalidArgumentException(\"Property '$property_name' not specified\");\n";
				}
				$body .= $indent."}\n";
			}
		}
		$deserializer = $this->type->getDeserializer('$'.$property_name, $indent);
		if (\trim($deserializer) !== '$'.$property_name) {
			$body .= $indent.'$'.$property_name.' = '.$deserializer.";\n\n";
		}
		return $body;
	}

	public function makeField(string $indent, string $def_visibility) : string
	{
		$property_name = $this->name;
		$doc_hint = $this->type->getPhpDocHint();
		$type_hint = $this->type->getArgumentHint();
		if ($type_hint) {
			$type_hint .= ' ';
		}
		$visibility = $this->field_visibility ?? $def_visibility;
		$body = '';
		if ($doc_hint && ($visibility === 'public')) {
			$body .= $indent."/** @var $doc_hint */\n";
		}
		$body .= $indent."$visibility $type_hint\$$property_name;\n";
		return $body;
	}

	public function makeEqualsFragment(string $peer_l_value, string $indent) : string
	{
		$property_name = $this->name;
		$exp1 = '$this->'.$property_name;
		$exp2 = $peer_l_value.'->'.$property_name;
		return $this->type->getEqualizer($exp1, $exp2, $indent);
	}

	public function makeUses() : iterable
	{
		yield from $this->type->getUses();
	}

}
