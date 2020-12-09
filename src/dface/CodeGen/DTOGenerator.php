<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class DTOGenerator
{

	private \IteratorAggregate $specSource;
	private ClassWriter $classWriter;
	private string $fieldsVisibility;

	public function __construct(
		\IteratorAggregate $specSource,
		ClassWriter $classWriter,
		string $fieldsVisibility = 'private'
	) {
		$this->specSource = $specSource;
		$this->classWriter = $classWriter;
		$visibilitySet = ['private', 'protected', 'public'];
		if (!\in_array($fieldsVisibility, $visibilitySet, true)) {
			throw new \InvalidArgumentException('Fields visibility must be one of ['.implode(', ', $visibilitySet).']');
		}
		$this->fieldsVisibility = $fieldsVisibility;
	}

	public function generate($force = false)
	{
		$lib_files = \glob(__DIR__.'/*.php');
		$tool_mtime = \array_reduce($lib_files, static function ($max, $item) {
			return \max($max, \filemtime($item));
		}, 0);
		/** @var Specification $spec */
		foreach ($this->specSource as $spec) {
			$className = $spec->getClassName();
			$targetModified = $this->classWriter->getTargetMTime($className);
			if ($force || $targetModified < $tool_mtime || $targetModified < $spec->getModified()) {
				try {
					$code = $this->generateDataClass($spec);
					$this->classWriter->writeClass($className, $code);
				} catch (\Exception $e) {
					throw new \RuntimeException($className->getFullName().' code-gen error: '.$e->getMessage(), 0, $e);
				}
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
		$body .= 'class '.$spec->getClassName()->getShortName()." implements JsonSerializable$imp {\n\n";
		$body .= $this->generateTraits($spec);
		$body .= $this->generateFields($spec);
		$body .= $this->generateConstructor($spec);
		$body .= $this->generateGetters($spec);
		$body .= $this->generateSetters($spec);
		$body .= $this->generateWithers($spec);
		$body .= $this->generateSerializerMethod($spec);
		$body .= $this->generateDeserializerMethod($spec);
		$body .= $this->generateEqualizerMethod($spec);
		$body .= $this->generateIsDirty();
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
			foreach ($field->makeUses($namespace) as $u) {
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
		$fields = $spec->getFields();
		$body = "\t/**\n";
		$body .= "\t * @param object|array \$data\n";
		$body .= "\t * @return self\n";
		$body .= "\t * @throws \\InvalidArgumentException\n";
		$body .= "\t */\n";
		$body .= "\t"."public static function deserialize(\$data) : self {\n";
		if (!$fields) {
			$body .= "\t\t"."if (!\is_array(\$data) && !\is_object(\$data)) {\n";
			$body .= "\t\t\t"."throw new \InvalidArgumentException('Array or object expected');\n";
			$body .= "\t\t"."}\n";
			$body .= "\t\t"."return new static();\n";
			$body .= "\t}\n";
			return $body;
		}
		$body .= "\t\t"."\$arr = (array)\$data;\n";
		$constructor_args = [];
		foreach ($fields as $field) {
			$body .= $field->makeDeserializerFragment("\t\t", $constructor_args);
		}
		if (\count($constructor_args) > 3) {
			$args_str = "\n\t\t\t".\implode(",\n\t\t\t", $constructor_args);
		} else {
			$args_str = \implode(', ', $constructor_args);
		}
		$body .= "\t\t".'return new static('.$args_str.");\n";
		$body .= "\t}\n\n";
		return $body;
	}

	private function generateEqualizerMethod(Specification $spec) : string
	{
		$fields = $spec->getFields();
		$body = "\t/**\n";
		$body .= "\t * @param mixed \$x\n";
		$body .= "\t * @return bool\n";
		$body .= "\t */\n";
		$body .= "\t"."public function equals(\$x) : bool {\n\n";
		$body .= "\t\t".'return $x instanceof static';

		foreach ($fields as $field) {
			$body .= "\n\n\t\t\t&& ".$field->makeEqualsFragment('$x', "\t\t\t");
		}

		$body .= ";\n";
		$body .= "\t}\n\n";
		return $body;
	}

	private function generateIsDirty() : string
	{
		$body = "\t"."public function isDirty() : bool {\n";
		$body .= "\t\t".'return $this->_dirty;'."\n";
		$body .= "\t}\n\n";
		return $body;
	}

	/**
	 * @param Specification $spec
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	private function generateSerializerMethod(Specification $spec) : string
	{
		$body = "\t/**\n";
		$body .= "\t * @return mixed\n";
		$body .= "\t */\n";
		$body .= "\t"."public function jsonSerialize()";
		$fields = $spec->getFields();
		if (empty($fields)) {
			$body .= " : object {\n";
			$body .= "\t\t"."return new \stdClass();\n";
			$body .= "\t}\n";
			return $body;
		}
		$body .= " : array {\n";
		$body .= "\n\t\t"."\$result = [];\n\n";
		foreach ($fields as $field) {
			$body .= $field->makeSerializerFragment('$result', "\t\t");
		}
		$body .= "\t\t"."return \$result;\n";
		$body .= "\t}\n\n";
		return $body;
	}

	private function generateFields(Specification $spec) : string
	{
		$body = '';
		foreach ($spec->getFields() as $field) {
			$body .= $field->makeField("\t", $this->fieldsVisibility);
		}
		$body .= "\t".$this->fieldsVisibility." bool \$_dirty = false;\n";
		return $body ? $body."\n" : '';
	}

	private function generateConstructor(Specification $spec) : string
	{
		$body = '';
		$constructor_params = [];
		$constructor_body = '';
		$constructor_doc = [];
		$fields_arr = $spec->getFields();
		foreach ($fields_arr as $i => $field) {
			$constructor_body .= $field->makeConstructorFragment("\t\t", $constructor_doc, $constructor_params);
		}
		if (\count($constructor_params) > 3) {
			$params_str = "\n\t\t".\implode(",\n\t\t", $constructor_params)."\n\t";
		} else {
			$params_str = \implode(', ', $constructor_params);
		}
		if ($constructor_doc) {
			$body .= "\t/**\n";
			$body .= "\t * ".\implode("\n\t * ", $constructor_doc);
			$body .= "\t */\n";
		}
		$body .= "\t".'public function __construct('.$params_str.") {\n";
		$body .= $constructor_body;
		$body .= "\t}\n\n";
		return $body;
	}

	private function generateGetters(Specification $spec) : string
	{
		$body = '';
		foreach ($spec->getFields() as $field) {
			$body .= $field->makeGetter("\t");
		}
		return $body;
	}

	private function generateSetters(Specification $spec) : string
	{
		$body = '';
		foreach ($spec->getFields() as $field) {
			$body .= $field->makeSetter("\t");
		}
		return $body;
	}

	private function generateWithers(Specification $spec) : string
	{
		$body = '';
		foreach ($spec->getFields() as $field) {
			$body .= $field->makeWither("\t");
		}
		return $body;
	}

	private static function fullTypeName(string $namespace, string $type_name) : string
	{
		return \strpos($type_name, '\\') === false ? $namespace.'\\'.$type_name : $type_name;
	}

}
