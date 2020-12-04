<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class DTOGenerator
{

	private \IteratorAggregate $specSource;
	private ClassWriter $classWriter;
	/** @var TypeDef[] */
	private array $types = [];
	/** @var TypeDef[] */
	private array $predefinedTypes;
	private string $fieldsVisibility;

	public function __construct(
		\IteratorAggregate $specSource,
		ClassWriter $classWriter,
		array $predefinedTypes,
		string $fieldsVisibility = 'private'
	) {
		$this->specSource = $specSource;
		$this->classWriter = $classWriter;
		$this->predefinedTypes = $predefinedTypes;
		$visibilitySet = ['private', 'protected', 'public'];
		if (!\in_array($fieldsVisibility, $visibilitySet, true)) {
			throw new \InvalidArgumentException('Fields visibility must be one of ['.implode(', ', $visibilitySet).']');
		}
		$this->fieldsVisibility = $fieldsVisibility;
	}

	public function generate()
	{
		$tool_mtime = \filemtime(__DIR__.'/.');
		/** @var Specification $spec */
		foreach ($this->specSource as $spec) {
			$className = $spec->getClassName();
			$targetModified = $this->classWriter->getTargetMTime($className);
			if ($targetModified < $tool_mtime || $targetModified < $spec->getModified()) {
				$code = $this->generateDataClass($spec);
				$this->classWriter->writeClass($className, $code);
			}
		}
	}

	/**
	 * @param Specification $spec
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	private function generateDataClass(Specification $spec) : string
	{
		$namespace = $spec->getClassName()->getNamespace();
		$body = '<?php'."\n\n";
		$body .= "/** Generated class. Don't edit manually. */\n\n";
		if ($namespace) {
			$body .= 'namespace '.\ltrim($namespace, '\\').";\n\n";
		}
		$body .= ($uses = $this->generateUses($spec)).($uses ? "\n" : '');
		$imp = $this->generateImplements($spec);
		if ($spec->getDeprecated()) {
			$body .= "/**\n";
			$body .= " * @deprecated\n";
			$body .= " */\n";
		}
		$type_hints = $this->prepareFieldsTypeHints($spec);
		$body .= 'class '.$spec->getClassName()->getShortName()." implements JsonSerializable$imp {\n\n";
		$body .= $this->generateTraits($spec);
		$body .= $this->generateFields($spec, $type_hints);
		$body .= $this->generateConstructor($spec);
		$body .= "\n";
		$body .= $this->generateGetters($spec, $type_hints);
		$body .= $this->generateSetters($spec, $type_hints);
		$body .= $this->generateWithers($spec, $type_hints);
		$body .= $this->generateSerializerMethod($spec);
		$body .= "\n";
		$body .= $this->generateDeserializerMethod($spec);
		$body .= "\n";
		$body .= "}\n";
		return $body;
	}

	/**
	 * @param Specification $spec
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	private function generateUses(Specification $spec) : string
	{
		$namespace = $spec->getClassName()->getNamespace();
		$uses = ['JsonSerializable' => "use JsonSerializable;\n"];
		foreach ($spec->getFields() as $field) {
			$type = $this->getType($namespace, $field->getType());
			foreach ($type->getUses($namespace) as $u) {
				$u = \ltrim($u, '\\');
				$uses[$u] = "use $u;\n";
			}
		}
		foreach ($spec->getInterfaces() as $i) {
			$fullType = self::fullTypeName($namespace, $i);
			$className = new ClassName($fullType);
			if ($className->getNamespace() !== $namespace) {
				$u = \ltrim($className->getFullName(), '\\');
				$uses[$u] = "use $u;\n";
			}
		}
		foreach ($spec->getTraits() as $i) {
			$fullType = self::fullTypeName($namespace, $i);
			$className = new ClassName($fullType);
			if ($className->getNamespace() !== $namespace) {
				$u = \ltrim($className->getFullName(), '\\');
				$uses[$u] = "use $u;\n";
			}
		}
		\ksort($uses, SORT_STRING);
		return \implode($uses);
	}

	private function generateImplements(Specification $spec) : string
	{
		$namespace = $spec->getClassName()->getNamespace();
		$arr = [];
		foreach ($spec->getInterfaces() as $i) {
			$fullType = self::fullTypeName($namespace, $i);
			$className = new ClassName($fullType);
			$iName = \ltrim($className->getShortName(), '\\');
			$arr[$iName] = $iName;
		}
		return $arr ? ', '.\implode(', ', $arr) : '';
	}

	private function generateTraits(Specification $spec) : string
	{
		$namespace = $spec->getClassName()->getNamespace();
		$arr = [];
		foreach ($spec->getTraits() as $i) {
			$fullType = self::fullTypeName($namespace, $i);
			$className = new ClassName($fullType);
			$tName = \ltrim($className->getShortName(), '\\');
			$arr[$tName] = $tName;
		}
		return $arr ? ("\t".'use '.implode(";\n\t".'use ', $arr).";\n\n") : '';
	}

	private function generateDeserializerMethod(Specification $spec) : string
	{
		$namespace = $spec->getClassName()->getNamespace();
		$fields = $spec->getFields();
		$body = "\t/**\n";
		$body .= "\t * @param object|array \$data\n";
		$body .= "\t * @return self\n";
		$body .= "\t * @throws \\InvalidArgumentException\n";
		$body .= "\t */\n";
		$body .= "\t"."public static function deserialize(\$data) : self {\n";
		if (!$fields) {
			$body .= "\t\t"."if(!\is_array(\$data) && !\is_object(\$data)){\n";
			$body .= "\t\t\t"."throw new \InvalidArgumentException('Array or object expected');\n";
			$body .= "\t\t"."}\n";
			$body .= "\t\t"."return new static();\n";
			$body .= "\t}\n";
			return $body;
		}
		$body .= "\t\t"."\$arr = (array)\$data;\n";
		$constructor_args = [];
		foreach ($fields as $field) {
			$property_name = $field->getName();
			$constructor_args[] = '$'.$property_name;
			if ($field->getMerged()) {
				$body .= "\t\t\$$property_name = \$arr;\n";
			} else {
				$default = $field->getSerializedDefault();
				$read_as = $field->getReadAs();
				if ($default !== null && $default->getCode() === 'null' && \count($read_as) === 1) {
					$alias = $read_as[0];
					$body .= "\t\t\$$property_name = \$arr['$alias'] ?? null;\n";
				} else {
					if ($default !== null) {
						$exported_ser_def = \str_replace("\n", "\n\t\t", $default->getCode());
						$body .= "\t\t\$$property_name = ".$exported_ser_def.";\n";
					}
					$first = true;
					foreach ($read_as as $alias) {
						if ($first) {
							$body .= "\t\t"."if(\\array_key_exists('$alias', \$arr)){\n";
							$first = false;
						} else {
							$body .= "\t\t}elseif(\\array_key_exists('$alias', \$arr)){\n";
						}
						$body .= "\t\t\t\$$property_name = \$arr['$alias'];\n";
					}
					if ($default === null) {
						$body .= "\t\t}else{\n";
						$body .= "\t\t\t"."throw new \\InvalidArgumentException(\"Property '$property_name' not specified\");\n";
					}
					$body .= "\t\t}\n";
				}
			}
			$type = $this->getType($namespace, $field->getType());
			$body .= "\t\t".$type->getDeserializer('$'.$property_name, "\t\t")."\n";
		}
		if (\count($constructor_args) > 3) {
			$args_str = "\n\t\t\t".\implode(",\n\t\t\t", $constructor_args);
		} else {
			$args_str = \implode(', ', $constructor_args);
		}
		$body .= "\t\t".'return new static('.$args_str.");\n";
		$body .= "\t}\n";
		return $body;
	}

	/**
	 * @param Specification $spec
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	private function generateSerializerMethod(Specification $spec) : string
	{
		$namespace = $spec->getClassName()->getNamespace();
		$body = "\t/**\n";
		$body .= "\t * @return mixed\n";
		$body .= "\t */\n";
		$body .= "\t"."public function jsonSerialize(){\n";
		$fields = $spec->getFields();
		if (empty($fields)) {
			$body .= "\t\t"."return new \stdClass();\n";
			$body .= "\t}\n";
			return $body;
		}
		$body .= "\n\t\t"."\$result = [];\n\n";
		$merge = [];
		foreach ($fields as $field) {
			$property_name = $field->getName();
			$getter = '$this->'.$property_name;
			$type = $this->getType($namespace, $field->getType());
			if ($field->getMerged()) {
				$target = "\$merge_${property_name}";
				$merge[$target] = $target.' = (array)'.$type->getSerializer($getter, $field->getNullAble(), "\t\t").";\n";
			} else {
				$silent = $field->getSilent();
				$s_indent = '';
				if ($silent) {
					$s_indent = "\t";
					$def = $field->getSerializedDefault();
					if ($def === null) {
						throw new \InvalidArgumentException("Field '$property_name' need 'empty' definition to be 'silent'");
					}
					$def_exp = $def->getCode();
					$body .= "\t\t"."if($getter !== $def_exp){\n";
				}
				foreach ($field->getWriteAs() as $target_name) {
					$target = "\$result['$target_name']";
					$body .= $s_indent."\t\t".$target.' = '.$type->getSerializer($getter, $field->getNullAble(),
							$s_indent."\t\t").";\n";
				}
				if ($silent) {
					$body .= "\t\t}\n";
				}
				$body .= "\n";
			}
		}
		if ($merge) {
			$body .= "\t\t".\implode("\t\t", $merge)."\n";
			$body .= "\t\t".'$result = \\array_replace($result, '.\implode(', ', \array_keys($merge)).");\n";
		}
		$body .= "\t\t"."return \$result;\n";
		$body .= "\t}\n";
		return $body;
	}

	private function generateFields(Specification $spec, array $type_hints) : string
	{
		$body = '';
		foreach ($spec->getFields() as $field) {
			$property_name = $field->getName();
			[$doc_hint, $type_hint] = $type_hints[$property_name];
			if ($type_hint) {
				$type_hint .= ' ';
			}
			$visibility = $field->getFieldVisibility();
			if ($visibility === null) {
				$visibility = $this->fieldsVisibility;
			}
			if ($doc_hint && ($visibility === 'public')) {
				$body .= "\t/** @var $doc_hint */\n";
			}
			$body .= "\t"."$visibility $type_hint\$$property_name;\n";
		}
		return $body ? $body . "\n" : '';
	}

	private function generateConstructor(Specification $spec) : string
	{
		$body = '';
		$constructor_params = [];
		$constructor_body = '';
		$constructor_doc = "\t/**\n";
		$fields_arr = $spec->getFields();
		$allow_default_from = PHP_INT_MAX;
		foreach ($fields_arr as $i => $field) {
			if ($field->getConstructorDefault() !== null) {
				$allow_default_from = \min($allow_default_from, $i);
			} else {
				$allow_default_from = PHP_INT_MAX;
			}
		}
		$namespace = $spec->getClassName()->getNamespace();
		foreach ($fields_arr as $i => $field) {
			$property_name = $field->getName();
			$type = $this->getType($namespace, $field->getType());
			$doc_hint = $type->getPhpDocHint();
			$type_hint = $type->getArgumentHint();
			$nullable = $field->getNullAble();

			$property_info = '';
			$default = $field->getConstructorDefault();
			$inline_def = '';
			$right_val = "\$$property_name";
			if ($default !== null) {
				if ($default->isEmbeddable()) {
					$def_code = \str_replace("\n", "\n\t\t", $default->getCode());
					$inline_def = ' = '.$def_code;
				} else {
					$nullable = true;
					$inline_def = ' = '.'null';
					$def_code = $default->getCode();
					$property_info = ' <code>?? '.\str_replace("\n", " ", $def_code).'</code>';
					$right_val = "$right_val ?? ".\str_replace("\n", "\n\t\t", $def_code);
				}
			}
			if ($i < $allow_default_from) {
				$inline_def = '';
			}
			if ($nullable) {
				$doc_hint .= '|null';
				if ($type_hint) {
					$type_hint = '?'.$type_hint;
				}
			}
			if ($type_hint) {
				$type_hint .= ' ';
			}
			$constructor_doc .= "\t * @param $doc_hint \$$property_name$property_info\n";
			$constructor_params[] = $type_hint.'$'.$property_name.$inline_def;
			$constructor_body .= "\t\t"."\$this->$property_name = $right_val;\n";
		}
		$constructor_doc .= "\t */\n";
		if (\count($constructor_params) > 3) {
			$params_str = "\n\t\t".\implode(",\n\t\t", $constructor_params)."\n\t";
		} else {
			$params_str = \implode(', ', $constructor_params);
		}
		if($constructor_params) {
			$body .= $constructor_doc;
		}
		$body .= "\t".'public function __construct('.$params_str."){\n";
		$body .= $constructor_body;
		$body .= "\t}\n";
		return $body;
	}

	private function prepareFieldsTypeHints(Specification $spec) : array
	{
		$namespace = $spec->getClassName()->getNamespace();
		$map = [];
		foreach ($spec->getFields() as $field) {
			$map[$field->getName()] = $this->getFieldTypeHints($namespace, $field);
		}
		return $map;
	}

	private function getFieldTypeHints(string $namespace, FieldDef $field) : array
	{
		$type = $this->getType($namespace, $field->getType());
		$doc_hint = $type->getPhpDocHint();
		$type_hint = $type->getArgumentHint();
		if ($field->getNullAble()) {
			$doc_hint .= '|null';
			if ($type_hint) {
				$type_hint = '?'.$type_hint;
			}
		}
		return [$doc_hint, $type_hint];
	}

	private function generateGetters(Specification $spec, array $type_hints) : string
	{
		$body = '';
		foreach ($spec->getFields() as $field) {
			$property_name = $field->getName();
			if ($field->getGetter()) {
				[$doc_hint, $type_hint] = $type_hints[$property_name];
				$body .= "\t/**\n";
				$body .= "\t * @return $doc_hint\n";
				$body .= "\t */\n";
				$ret_hint = $type_hint ? (' : '.$type_hint.' ') : '';
				$body .= "\t".'public function get'.self::camelCase($property_name)."()$ret_hint{\n";
				$body .= "\t\t"."return \$this->$property_name;\n";
				$body .= "\t}\n\n";
			}
		}
		return $body;
	}

	private function generateSetters(Specification $spec, array $type_hints) : string
	{
		$body = '';
		foreach ($spec->getFields() as $field) {
			$property_name = $field->getName();
			if ($field->getSetter()) {
				[$doc_hint, $type_hint] = $type_hints[$property_name];
				$type_hint .= $type_hint !== '' ? ' ' : '';
				$body .= "\t/**\n";
				$body .= "\t * @param $doc_hint \$val\n";
				$body .= "\t */\n";
				$body .= "\t".'public function set'.self::camelCase($property_name)."($type_hint\$val) : void {\n";
				$body .= "\t\t\$this->$property_name = \$val;\n";
				$body .= "\t}\n\n";
			}
		}
		return $body;
	}

	private function generateWithers(Specification $spec, array $type_hints) : string
	{
		$body = '';
		foreach ($spec->getFields() as $field) {
			$property_name = $field->getName();
			if ($field->getWither()) {
				[$doc_hint, $type_hint] = $type_hints[$property_name];
				$type_hint .= $type_hint !== '' ? ' ' : '';
				$body .= "\t/**\n";
				$body .= "\t * @param $doc_hint \$val\n";
				$body .= "\t * @return self\n";
				$body .= "\t */\n";
				$body .= "\t".'public function with'.self::camelCase($property_name)."($type_hint\$val) : self {\n";
				$body .= "\t\t\$clone = clone \$this;\n";
				$body .= "\t\t\$clone->$property_name = \$val;\n";
				$body .= "\t\t"."return \$clone;\n";
				$body .= "\t}\n\n";
			}
		}
		return $body;
	}

	private static function camelCase(string $property_name) : string
	{
		$camelCase = \preg_replace_callback('/_([a-z])/', static function ($m) {
			return \strtoupper($m[1]);
		}, $property_name);
		return \strtoupper($camelCase[0]).\substr($camelCase, 1);
	}

	private static function fullTypeName(string $namespace, string $type_name) : string
	{
		return \strpos($type_name, '\\') === false ? $namespace.'\\'.$type_name : $type_name;
	}

	/**
	 * @param $namespace
	 * @param $type_name
	 * @return TypeDef|mixed|string
	 * @throws \InvalidArgumentException
	 */
	private function getType(string $namespace, $type_name)
	{
		if ($type_name instanceof TypeDef) {
			return $type_name;
		}
		if (\is_array($type_name)) {
			$type_name = $type_name[0].'[]';
		}
		if (isset($this->predefinedTypes[$type_name])) {
			return $this->predefinedTypes[$type_name];
		}
		$full_name = self::fullTypeName($namespace, $type_name);
		if (!isset($this->types[$full_name])) {
			if (\substr($type_name, -2) === '[]') {
				$el_type = \substr($type_name, 0, -2);
				if ($el_type === '') {
					throw new \InvalidArgumentException('Specify element type');
				}
				$inner_type = $this->getType($namespace, $el_type);
				$this->types[$full_name] = new ArrayType($inner_type);
			} elseif (\substr($type_name, -2) === '{}') {
				$el_type = \substr($type_name, 0, -2);
				if ($el_type === '') {
					throw new \InvalidArgumentException('Specify element type');
				}
				$inner_type = $this->getType($namespace, $el_type);
				$this->types[$full_name] = new MapType($inner_type);
			} elseif (\is_a($full_name, TypeDef::class)) {
				$this->types[$full_name] = new $full_name;
			} else {
				$this->types[$full_name] = new DynamicTypeDef(new ClassName($full_name));
			}
		}
		return $this->types[$full_name];
	}

}
