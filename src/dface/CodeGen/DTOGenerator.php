<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class DTOGenerator
{

	/** @var \IteratorAggregate */
	private $specSource;
	/** @var ClassWriter */
	private $classWriter;
	/** @var TypeDef[] */
	private $types = [];
	/** @var TypeDef[] */
	private $predefinedTypes;
	/** @var string */
	private $targetVersion;
	/** @var string */
	private $fieldsVisibility;

	/**
	 * @param \IteratorAggregate $specSource
	 * @param ClassWriter $classWriter
	 * @param array $predefinedTypes
	 * @param string $target_version
	 * @param string $fieldsVisibility
	 * @throws \InvalidArgumentException
	 */
	public function __construct(
		\IteratorAggregate $specSource,
		ClassWriter $classWriter,
		array $predefinedTypes,
		$target_version = PHP_VERSION,
		$fieldsVisibility = 'private'
	) {
		$this->specSource = $specSource;
		$this->classWriter = $classWriter;
		$this->predefinedTypes = $predefinedTypes;
		$this->targetVersion = $target_version;
		$visibilitySet = ['private', 'protected', 'public'];
		if (!\in_array($fieldsVisibility, $visibilitySet, true)) {
			throw new \InvalidArgumentException('Fields visibility must be one of ['.implode(', ', $visibilitySet).']');
		}
		$this->fieldsVisibility = $fieldsVisibility;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function generate()
	{
		$tool_mtime = filemtime(__DIR__.'/.');
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
	private function generateDataClass(Specification $spec)
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
			$body .= "\t/**\n";
			$body .= "\t * @deprecated\n";
			$body .= "\t */\n";
		}
		$body .= 'class '.$spec->getClassName()->getShortName()." implements \\JsonSerializable$imp {\n\n";
		$body .= $this->generateTraits($spec);
		$body .= $this->generateFields($spec);
		$body .= "\n";
		$body .= $this->generateConstructor($spec);
		$body .= "\n";
		$body .= $this->generateGetters($spec);
		$body .= $this->generateSetters($spec);
		$body .= $this->generateWithers($spec);
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
	private function generateUses(Specification $spec)
	{
		$namespace = $spec->getClassName()->getNamespace();
		$uses = [];
		foreach ($spec->getFields() as $field) {
			$type = $this->getType($namespace, $field->getType());
			foreach ($type->getUses($namespace) as $u) {
				$u = \ltrim($u, '\\');
				$uses[$u] = "use $u;\n";
			}
		}
		foreach ($spec->getInterfaces() as $i) {
			$fullType = $this->fullTypeName($namespace, $i);
			$className = new ClassName($fullType);
			if ($className->getNamespace() !== $namespace) {
				$u = \ltrim($className->getFullName(), '\\');
				$uses[$u] = "use $u;\n";
			}
		}
		foreach ($spec->getTraits() as $i) {
			$fullType = $this->fullTypeName($namespace, $i);
			$className = new ClassName($fullType);
			if ($className->getNamespace() !== $namespace) {
				$u = \ltrim($className->getFullName(), '\\');
				$uses[$u] = "use $u;\n";
			}
		}
		return \implode($uses);
	}

	private function generateImplements(Specification $spec)
	{
		$namespace = $spec->getClassName()->getNamespace();
		$arr = [];
		foreach ($spec->getInterfaces() as $i) {
			$fullType = $this->fullTypeName($namespace, $i);
			$className = new ClassName($fullType);
			$iName = \ltrim($className->getShortName(), '\\');
			$arr[$iName] = $iName;
		}
		return $arr ? ', '.\implode(', ', $arr) : '';
	}

	private function generateTraits(Specification $spec)
	{
		$namespace = $spec->getClassName()->getNamespace();
		$arr = [];
		foreach ($spec->getTraits() as $i) {
			$fullType = $this->fullTypeName($namespace, $i);
			$className = new ClassName($fullType);
			$tName = \ltrim($className->getShortName(), '\\');
			$arr[$tName] = $tName;
		}
		return $arr ? ("\t".'use '.implode(";\n\t".'use ', $arr).";\n\n") : '';
	}

	/**
	 * @param Specification $spec
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	private function generateDeserializerMethod(Specification $spec)
	{
		$namespace = $spec->getClassName()->getNamespace();
		$ret_hint = '';
		$support_ret_hint = \version_compare($this->targetVersion, '7.1') >= 0;
		if ($support_ret_hint) {
			$ret_hint = ' : '.$spec->getClassName()->getShortName().' ';
		}
		$body = "\t/**\n";
		$body .= "\t * @param array \$arr\n";
		$body .= "\t * @return self\n";
		$body .= "\t * @throws \\InvalidArgumentException\n";
		$body .= "\t */\n";
		$body .= "\t"."public static function deserialize(array \$arr)$ret_hint{\n";
		$fields = $spec->getFields();
		if (empty($fields)) {
			$body .= "\t\t"."if(\$arr !== []){\n";
			$body .= "\t\t\t"."throw new \InvalidArgumentException('Empty array expected');\n";
			$body .= "\t\t"."}\n";
			$body .= "\t\t"."return new static();\n";
			$body .= "\t}\n";
			return $body;
		}
		$constructor_args = [];
		foreach ($fields as $field) {
			$property_name = $field->getName();
			$constructor_args[] = '$'.$property_name;
			if ($field->getMerged()) {
				$body .= "\t\t\$$property_name = \$arr;\n";
			}else {
				$has_def = $field->hasSerializedDefault();
				if ($has_def) {
					$exported_ser_def = $this->varExport($field->getSerializedDefault());
					$exported_ser_def = \str_replace("\n", "\n\t\t", $exported_ser_def);
					$body .= "\t\t\$$property_name = ".$exported_ser_def.";\n";
				}
				$first = true;
				foreach ($field->getReadAs() as $alias) {
					if ($first) {
						$body .= "\t\t"."if(\\array_key_exists('$alias', \$arr)){\n";
						$first = false;
					}else {
						$body .= "\t\t}elseif(\\array_key_exists('$alias', \$arr)){\n";
					}
					$body .= "\t\t\t\$$property_name = \$arr['$alias'];\n";
				}
				if (!$has_def) {
					$body .= "\t\t}else{\n";
					$body .= "\t\t\t"."throw new \\InvalidArgumentException(\"Property '$property_name' not specified\");\n";
				}
				$body .= "\t\t}\n";
			}
			$type = $this->getType($namespace, $field->getType());
			$body .= "\t\t".$type->getDeserializer('$'.$property_name, '$'.$property_name, "\t\t")."\n";
		}
		if (\count($constructor_args) > 3) {
			$args_str = "\n\t\t\t".\implode(",\n\t\t\t", $constructor_args);
		}else {
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
	private function generateSerializerMethod(Specification $spec)
	{
		$namespace = $spec->getClassName()->getNamespace();
		$body = "\t/**\n";
		$body .= "\t * @return mixed\n";
		$body .= "\t */\n";
		$body .= "\t"."public function jsonSerialize(){\n";
		$fields = $spec->getFields();
		if (empty($fields)) {
			$body .= "\t\t"."return [];\n";
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
				$merge[$target] = $target.' = '.$type->getSerializer($getter, $field->getNullAble(), "\t\t").";\n";
			}else {
				$silent = $field->getSilent();
				$s_indent = '';
				if ($silent) {
					$s_indent = "\t";
					$def = $field->getSerializedDefault();
					$def_exp = $this->varExport($def);
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

	/**
	 * @param Specification $spec
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	private function generateFields(Specification $spec)
	{
		$namespace = $spec->getClassName()->getNamespace();
		$body = '';
		foreach ($spec->getFields() as $field) {
			$property_name = $field->getName();
			$type = $this->getType($namespace, $field->getType());
			$type_hint = $type->getPhpDocHint();
			$null_able = $field->getNullAble() ? '|null' : '';
			$body .= "\t/** @var $type_hint$null_able */\n";
			$visibility = $field->getFieldVisibility();
			if ($visibility === null) {
				$visibility = $this->fieldsVisibility;
			}
			$body .= "\t"."$visibility \$$property_name;\n";
		}
		return $body;
	}

	/**
	 * @param Specification $spec
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	private function generateConstructor(Specification $spec)
	{
		$namespace = $spec->getClassName()->getNamespace();
		$body = '';
		$constructor_params = [];
		$constructor_body = '';
		$hint_scalars = \version_compare($this->targetVersion, '7.0') >= 0;
		$hint_nulls = \version_compare($this->targetVersion, '7.1') >= 0;
		foreach ($spec->getFields() as $field) {
			$property_name = $field->getName();
			$type = $this->getType($namespace, $field->getType());
			$type_hint = $type->getArgumentHint();
			$is_scalar = $type instanceof ScalarType;
			$type_hint .= $type_hint !== '' ? ' ' : '';
			if ($hint_nulls && $type_hint && $field->getNullAble()) {
				$type_hint = '?'.$type_hint;
			}
			$has_def = $field->hasConstructorDefault();
			$def = '';
			if ($has_def) {
				$def = ' = '.$this->varExport($field->getConstructorDefault());
			}
			$right_val = "\$$property_name";
			if ($is_scalar && !$hint_scalars) {
				$type_hint = '';
				if ($field->getNullAble()) {
					$right_val = "$right_val === null ? null : (".$type->getArgumentHint().") $right_val";
				}else {
					$right_val = '('.$type->getArgumentHint().") $right_val";
				}
			}
			$constructor_params[] = $type_hint.'$'.$property_name.$def;
			$constructor_body .= "\t\t"."\$this->$property_name = $right_val;\n";
		}
		if (\count($constructor_params) > 3) {
			$params_str = "\n\t\t".\implode(",\n\t\t", $constructor_params)."\n\t";
		}else {
			$params_str = \implode(', ', $constructor_params);
		}
		$body .= "\t".'public function __construct('.$params_str."){\n";
		$body .= $constructor_body;
		$body .= "\t}\n";
		return $body;
	}

	/**
	 * @param Specification $spec
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	private function generateGetters(Specification $spec)
	{
		$namespace = $spec->getClassName()->getNamespace();
		$body = '';
		$support_ret_hint = \version_compare($this->targetVersion, '7.1') >= 0;
		foreach ($spec->getFields() as $field) {
			$type = $this->getType($namespace, $field->getType());
			$doc_hint = $type->getPhpDocHint();
			$null_able = $field->getNullAble() ? '|null' : '';
			$doc_hint .= $null_able;
			$body .= "\t/**\n";
			$body .= "\t * @return $doc_hint\n";
			$body .= "\t */\n";
			$property_name = $field->getName();
			$ret_hint = '';
			if ($support_ret_hint && ($arg_hint = $type->getArgumentHint())) {
				if ($field->getNullAble()) {
					$arg_hint = '?'.$arg_hint;
				}
				$ret_hint = ' : '.$arg_hint.' ';
			}
			$body .= "\t".'public function get'.$this->camelCase($property_name)."()$ret_hint{\n";
			$body .= "\t\t"."return \$this->$property_name;\n";
			$body .= "\t}\n\n";
		}
		return $body;
	}

	/**
	 * @param Specification $spec
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	private function generateSetters(Specification $spec)
	{
		$namespace = $spec->getClassName()->getNamespace();
		$body = '';
		$hint_scalars = \version_compare($this->targetVersion, '7.0') >= 0;
		$hint_nulls = \version_compare($this->targetVersion, '7.1') >= 0;
		foreach ($spec->getFields() as $field) {
			$property_name = $field->getName();
			if ($field->getSetter()) {
				$type = $this->getType($namespace, $field->getType());
				$doc_hint = $type->getPhpDocHint();
				$type_hint = $type->getArgumentHint();
				if ($type instanceof ScalarType && !$hint_scalars) {
					$type_hint = '';
				}
				if ($hint_nulls && $type_hint && $field->getNullAble()) {
					$type_hint = '?'.$type_hint;
				}
				$type_hint .= $type_hint !== '' ? ' ' : '';
				$def_null = $hint_nulls ? '' : ' = null';
				$body .= "\t/**\n";
				$body .= "\t * @param $doc_hint \$val\n";
				$body .= "\t */\n";
				$body .= "\t".'public function set'.$this->camelCase($property_name)."($type_hint\$val$def_null){\n";
				$body .= "\t\t\$this->$property_name = \$val;\n";
				$body .= "\t}\n\n";
			}
		}
		return $body;
	}

	/**
	 * @param Specification $spec
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	private function generateWithers(Specification $spec)
	{
		$namespace = $spec->getClassName()->getNamespace();
		$body = '';
		$hint_scalars = \version_compare($this->targetVersion, '7.0') >= 0;
		$ret_hint = \version_compare($this->targetVersion, '7.1') >= 0 ? ' : self ' : '';
		$hint_nulls = \version_compare($this->targetVersion, '7.1') >= 0;
		foreach ($spec->getFields() as $field) {
			$property_name = $field->getName();
			if ($field->getWither()) {
				$type = $this->getType($namespace, $field->getType());
				$doc_hint = $type->getPhpDocHint();
				$type_hint = $type->getArgumentHint();
				if ($type instanceof ScalarType && !$hint_scalars) {
					$type_hint = '';
				}
				$def_null = '';
				if($field->getNullAble()){
					$doc_hint .= '|null';
					if ($hint_nulls) {
						if($type_hint) {
							$type_hint = '?'.$type_hint;
						}
					}else{
						$def_null = ' = null';
					}
				}
				$type_hint .= $type_hint !== '' ? ' ' : '';
				$body .= "\t/**\n";
				$body .= "\t * @param $doc_hint \$val\n";
				$body .= "\t * @return self\n";
				$body .= "\t */\n";
				$body .= "\t".'public function with'.$this->camelCase($property_name)."($type_hint\$val$def_null)$ret_hint{\n";
				$body .= "\t\t\$clone = clone \$this;\n";
				$body .= "\t\t\$clone->$property_name = \$val;\n";
				$body .= "\t\t"."return \$clone;\n";
				$body .= "\t}\n\n";
			}
		}
		return $body;
	}

	private function camelCase($property_name)
	{
		$camelCase = \preg_replace_callback('/_([a-z])/', function ($m) {
			return \strtoupper($m[1]);
		}, $property_name);
		return \strtoupper($camelCase[0]).\substr($camelCase, 1);
	}

	private function fullTypeName($namespace, $type_name)
	{
		return \strpos($type_name, '\\') === false ? $namespace.'\\'.$type_name : $type_name;
	}

	/**
	 * @param $namespace
	 * @param $type_name
	 * @return TypeDef|mixed|string
	 * @throws \InvalidArgumentException
	 */
	private function getType($namespace, $type_name)
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
		$full_name = $this->fullTypeName($namespace, $type_name);
		if (!isset($this->types[$full_name])) {
			if (\substr($type_name, -2) === '[]') {
				$el_type = \substr($type_name, 0, -2);
				if ($el_type === '') {
					throw new \InvalidArgumentException('Specify element type');
				}
				$inner_type = $this->getType($namespace, $el_type);
				$this->types[$full_name] = new ArrayType($inner_type);
			}elseif (\substr($type_name, -2) === '{}') {
				$el_type = \substr($type_name, 0, -2);
				if ($el_type === '') {
					throw new \InvalidArgumentException('Specify element type');
				}
				$inner_type = $this->getType($namespace, $el_type);
				$this->types[$full_name] = new MapType($inner_type);
			}elseif (\is_a($full_name, TypeDef::class)) {
				$this->types[$full_name] = new $full_name;
			}else {
				$this->types[$full_name] = new DynamicTypeDef(new ClassName($full_name));
			}
		}
		return $this->types[$full_name];
	}

	private function varExport($var)
	{
		if ($var === null) {
			return 'null';
		}
		if ($var === []) {
			return '[]';
		}
		return \var_export($var, true);
	}

}
