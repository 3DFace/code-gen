<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class PhpFilesSpecSource implements \IteratorAggregate
{

	private string $baseNamespace;
	private string $definitionsDir;
	private string $relativeName;

	public function __construct(string $baseNamespace, string $definitionsDir, string $relativeName = '')
	{
		$this->baseNamespace = $baseNamespace;
		$this->definitionsDir = $definitionsDir;
		$this->relativeName = $relativeName;
	}

	public function getIterator()
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
		foreach ($definitions as $defName => $definition) {
			$defPath = $relativeFilename.'/'.$defName;
			$namespace = \trim($this->baseNamespace.\str_replace('/', '\\', \substr($relativeFilename, 0, -4)), '\\');
			$className = $namespace.'\\'.$defName;
			$fields = [];
			$interfaces = [];
			$traits = [];
			foreach ($definition as $name => $arr) {
				if ($name[0] !== '@') {
					$fields[] = $this->createFieldDef($name, $arr, $defPath);
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
			yield new Specification(new ClassName($className), $fields, $interfaces, $traits, $deprecated, $modified);
		}
	}

	private function createFieldDef(string $name, $arr, string $defPath)
	{
		if (!\is_array($arr)) {
			if (\is_string($arr) || $arr instanceof TypeDef) {
				$arr = ['type' => $arr];
			} else {
				throw new \InvalidArgumentException("Bad field definition type at $defPath->{$name}");
			}
		}

		$read_as = [$name];
		if (isset($arr['alias'])) {
			$read_as[] = $arr['alias'];
		}
		if (isset($arr['read_as'])) {
			$read_as = $arr['read_as'];
			if (!\is_array($read_as)) {
				$read_as = [$read_as];
			}
		}

		$write_as = [$name];
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

		return new FieldDef(
			$name,
			$arr['type'],
			$read_as,
			$write_as,
			$default,
			$empty,
			$arr['with'] ?? false,
			$arr['set'] ?? false,
			$arr['merged'] ?? false,
			$arr['silent'] ?? false,
			$arr['null'] ?? ($default && ($default->getCode() === 'null')),
			$arr['field_visibility'] ?? null
		);
	}

}
