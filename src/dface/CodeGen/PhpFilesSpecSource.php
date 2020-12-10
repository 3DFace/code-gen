<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class PhpFilesSpecSource implements \IteratorAggregate
{

	private string $base_name_space;
	private string $definitions_dir;
	private string $relative_name;
	/** @var TypeDef[] */
	private array $types;

	public function __construct(array $predefined_types, string $base_namespace, string $definitions_dir, string $relative_name = '')
	{
		$this->types = $predefined_types;
		$this->base_name_space = $base_namespace;
		$this->definitions_dir = $definitions_dir;
		$this->relative_name = $relative_name;
	}

	public function getIterator() : \Generator
	{
		foreach ($this->walkDir($this->relative_name) as $name) {
			yield $name;
		}
	}

	private function walkDir(string $relative_name) : iterable
	{
		$d = \dir($this->definitions_dir.$relative_name);
		while (false !== ($entry = $d->read())) {
			if (!\in_array($entry, ['.', '..'], true)) {
				$fullName = $this->definitions_dir.$relative_name.'/'.$entry;
				if (\is_dir($fullName)) {
					foreach ($this->walkDir($relative_name.'/'.$entry) as $name) {
						yield $name;
					}
				} else {
					foreach ($this->walkFile($relative_name.'/'.$entry) as $name) {
						yield $name;
					}
				}
				$result[] = $entry;
			}
		}
		$d->close();
	}

	private function walkFile($relative_file_name) : iterable
	{
		/** @var array[] $definitions */
		$modified = \filemtime($this->definitions_dir.$relative_file_name);
		/** @noinspection PhpIncludeInspection */
		$definitions = include $this->definitions_dir.$relative_file_name;
		$deprecated = false;
		$namespace = \trim($this->base_name_space.\str_replace('/', '\\', \substr($relative_file_name, 0, -4)), '\\');
		foreach ($definitions as $def_name => $definition_items) {
			$defPath = $relative_file_name.'/'.$def_name;
			$class_name = new ClassName($namespace.'\\'.$def_name);
			$fields = [];
			$interfaces = [];
			$traits = [];
			foreach ($definition_items as $name => $dev_value) {
				if ($name[0] !== '@') {
					$fields[] = $this->createFieldDef($name, $dev_value, $defPath);
				} else {
					$optName = \substr($name, 1);
					switch ($optName) {
						case 'implements':
							$interfaces = \is_array($dev_value) ? $dev_value : [$dev_value];
							break;
						case 'traits':
							$traits = \is_array($dev_value) ? $dev_value : [$dev_value];
							break;
						case 'deprecated':
							$deprecated = (bool)$dev_value;
							break;
						default:
							throw new \InvalidArgumentException("Unsupported option $optName");
					}
				}
			}
			yield new Specification($class_name, $fields, $interfaces, $traits, $deprecated, $modified);
		}
	}

	/**
	 * @param $type_name
	 * @param bool $nullable
	 * @return TypeDef
	 */
	private function getType(string $type_name, bool $nullable) : TypeDef
	{
		$key_name = $type_name;
		if ($nullable) {
			$key_name .= '_nullable';
		}

		if (isset($this->types[$key_name])) {
			return $this->types[$key_name];
		}

		if ($nullable) {
			$new_type = $this->getType($type_name, false)->createNullable();
		} elseif (\substr($type_name, -2) === '[]') {
			$el_type = \substr($type_name, 0, -2);
			if ($el_type === '') {
				throw new \InvalidArgumentException('Specify element type');
			}
			$inner_type = $this->getType($el_type, false);
			$new_type = new ArrayType($inner_type);
		} elseif (\substr($type_name, -3) === '[?]') {
			$el_type = \substr($type_name, 0, -3);
			if ($el_type === '') {
				throw new \InvalidArgumentException('Specify element type');
			}
			$inner_type = $this->getType($el_type, true);
			$new_type = new ArrayType($inner_type);
		} elseif (\substr($type_name, -2) === '{}') {
			$el_type = \substr($type_name, 0, -2);
			if ($el_type === '') {
				throw new \InvalidArgumentException('Specify element type');
			}
			$inner_type = $this->getType($el_type, false);
			$new_type = new MapType($inner_type);
		} elseif (\substr($type_name, -3) === '{?}') {
			$el_type = \substr($type_name, 0, -3);
			if ($el_type === '') {
				throw new \InvalidArgumentException('Specify element type');
			}
			$inner_type = $this->getType($el_type, true);
			$new_type = new MapType($inner_type);
		} elseif (\is_a($type_name, TypeDef::class)) {
			$new_type = new $type_name;
		} else {
			$new_type = new DynamicTypeDef(new ClassName($type_name));
		}
		$this->types[$key_name] = $new_type;
		return $new_type;
	}

	private function createFieldDef(string $field_name, $def_value, string $def_path) : FieldDef
	{
		if($def_value instanceof FieldDef){
			return $def_value;
		}

		if (!\is_array($def_value)) {
			if (\is_string($def_value) || $def_value instanceof TypeDef) {
				$arr = ['type' => $def_value];
			} else {
				throw new \InvalidArgumentException("Bad field definition type at $def_path->{$field_name}");
			}
		}else{
			$arr = $def_value;
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
		$type = $arr['type'];
		if (!$type instanceof TypeDef) {
			$type = $this->getType($type, $nullable);
		}

		return new DefaultFieldDef(
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
