<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class PhpFilesSpecSource implements \IteratorAggregate
{

	/** @var callable[] */
	private array $predefinedTypesFactories;
	/** @var TypeDef[] */
	private array $predefinedTypes;
	private string $baseNamespace;
	private string $definitionsDir;
	private string $relativeName;
	/** @var TypeDef[] */
	private array $types = [];

	public function __construct(array $predefinedTypesFactories, string $baseNamespace, string $definitionsDir, string $relativeName = '')
	{
		$this->predefinedTypesFactories = $predefinedTypesFactories;
		$this->baseNamespace = $baseNamespace;
		$this->definitionsDir = $definitionsDir;
		$this->relativeName = $relativeName;
	}

	public function getIterator() : \Generator
	{
		foreach ($this->walkDir($this->relativeName) as $name) {
			yield $name;
		}
	}

	private function walkDir(string $relativeName) : iterable
	{
		$d = \dir($this->definitionsDir.$relativeName);
		while (false !== ($entry = $d->read())) {
			if (!\in_array($entry, ['.', '..'], true)) {
				$fullName = $this->definitionsDir.$relativeName.'/'.$entry;
				if (\is_dir($fullName)) {
					foreach ($this->walkDir($relativeName.'/'.$entry) as $name) {
						yield $name;
					}
				} else {
					foreach ($this->walkFile($relativeName.'/'.$entry) as $name) {
						yield $name;
					}
				}
				$result[] = $entry;
			}
		}
		$d->close();
	}

	private function walkFile($relativeFilename) : iterable
	{
		/** @var array[] $definitions */
		$modified = \filemtime($this->definitionsDir.$relativeFilename);
		/** @noinspection PhpIncludeInspection */
		$definitions = include $this->definitionsDir.$relativeFilename;
		$deprecated = false;
		$namespace = \trim($this->baseNamespace.\str_replace('/', '\\', \substr($relativeFilename, 0, -4)), '\\');
		foreach ($definitions as $defName => $definition) {
			$defPath = $relativeFilename.'/'.$defName;
			$className = new ClassName($namespace.'\\'.$defName);
			$fields = [];
			$interfaces = [];
			$traits = [];
			foreach ($definition as $name => $arr) {
				if ($name[0] !== '@') {
					$fields[] = $this->createFieldDef($className, $name, $arr, $defPath);
				} else {
					$optName = \substr($name, 1);
					switch ($optName) {
						case 'implements':
							$interfaces = \is_array($arr) ? $arr : [$arr];
							break;
						case 'traits':
							$traits = \is_array($arr) ? $arr : [$arr];
							break;
						case 'deprecated':
							$deprecated = (bool)$arr;
							break;
						default:
							throw new \InvalidArgumentException("Unsupported option $optName");
					}
				}
			}
			yield new Specification($className, $fields, $interfaces, $traits, $deprecated, $modified);
		}
	}

	private static function fullTypeName(string $namespace, string $type_name) : string
	{
		return \strpos($type_name, '\\') === false ? $namespace.'\\'.$type_name : $type_name;
	}

	/**
	 * @param string $namespace
	 * @param $type_name
	 * @param bool $nullable
	 * @return TypeDef
	 */
	private function getType(string $namespace, $type_name, bool $nullable) : TypeDef
	{
		if ($type_name instanceof TypeDef) {
			return $type_name;
		}
		if (\is_array($type_name)) {
			$type_name = $type_name[0].'[]';
		}
		if (isset($this->predefinedTypesFactories[$type_name])) {
			$factory = $this->predefinedTypesFactories[$type_name];
			if(\is_callable($factory)) {
				if ($nullable) {
					$type_name .= '_nullable';
				}
				if (!isset($this->predefinedTypes[$type_name])) {
					$this->predefinedTypes[$type_name] = $factory($nullable);
				}
			}else{
				$this->predefinedTypes[$type_name] = $factory;
			}
			return $this->predefinedTypes[$type_name];
		}
		$full_name = self::fullTypeName($namespace, $type_name);
		$key_name = $full_name;
		if($nullable){
			$key_name .= '_nullable';
		}
		if (!isset($this->types[$key_name])) {
			if (\substr($type_name, -2) === '[]') {
				$el_type = \substr($type_name, 0, -2);
				if ($el_type === '') {
					throw new \InvalidArgumentException('Specify element type');
				}
				$inner_type = $this->getType($namespace, $el_type, false);
				$this->types[$key_name] = new ArrayType($inner_type, $nullable);
			} elseif (\substr($type_name, -2) === '{}') {
				$el_type = \substr($type_name, 0, -2);
				if ($el_type === '') {
					throw new \InvalidArgumentException('Specify element type');
				}
				$inner_type = $this->getType($namespace, $el_type, false);
				$this->types[$key_name] = new MapType($inner_type, $nullable);
			} elseif (\is_a($full_name, TypeDef::class)) {
				$this->types[$key_name] = new $full_name;
			} else {
				$this->types[$key_name] = new DynamicTypeDef(new ClassName($full_name), $nullable);
			}
		}
		return $this->types[$key_name];
	}

	private function createFieldDef(ClassName $class_name, string $field_name, $arr, string $defPath) : FieldDef
	{
		if (!\is_array($arr)) {
			if (\is_string($arr) || $arr instanceof TypeDef) {
				$arr = ['type' => $arr];
			} else {
				throw new \InvalidArgumentException("Bad field definition type at $defPath->{$field_name}");
			}
		}

		$read_as = [$field_name];
		if (isset($arr['alias'])) {
			$read_as[] = $arr['alias'];
		}
		if (isset($arr['read_as'])) {
			$read_as = $arr['read_as'];
			if (!\is_array($read_as)) {
				$read_as = [$read_as];
			}
		}

		$write_as = [$field_name];
		if (isset($arr['write_as'])) {
			$write_as = $arr['write_as'];
			if (!\is_array($write_as)) {
				$write_as = [$write_as];
			}
		}

		$default = null;
		if (\array_key_exists('default', $arr)) {
			$default = DefaultDef::fromValue($arr['default']);
		}
		if (\array_key_exists('default_code', $arr)) {
			$default = new DefaultDef($arr['default_code'], false);
		}

		$empty = null;
		if (\array_key_exists('empty', $arr)) {
			$empty = DefaultDef::fromValue($arr['empty']);
		}
		if (\array_key_exists('empty_code', $arr)) {
			$empty = new DefaultDef($arr['empty_code'], false);
		}

		$nullable = $arr['null'] ?? ($default && ($default->getCode() === 'null'));
		$type = $this->getType($class_name->getNamespace(), $arr['type'], $nullable);

		return new FieldDef(
			$field_name,
			$type,
			$read_as,
			$write_as,
			$default,
			$empty,
			$arr['with'] ?? false,
			$arr['set'] ?? false,
			$arr['get'] ?? true,
			$arr['merged'] ?? false,
			$arr['silent'] ?? false,
			$arr['field_visibility'] ?? null
		);
	}

}
