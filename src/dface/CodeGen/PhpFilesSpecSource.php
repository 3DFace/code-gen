<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class PhpFilesSpecSource implements \IteratorAggregate {

	/** @var string */
	private $baseNamespace;
	/** @var string */
	private $definitionsDir;
	/** @var string */
	private $relativeName;

	/**
	 * PhpFilesSpecSource constructor.
	 * @param string $baseNamespace
	 * @param string $definitionsDir
	 * @param string $relativeName
	 */
	public function __construct($baseNamespace, $definitionsDir, $relativeName = ''){
		$this->baseNamespace = $baseNamespace;
		$this->definitionsDir = $definitionsDir;
		$this->relativeName = $relativeName;
	}

	public function getIterator(){
		foreach($this->walkDir($this->relativeName) as $name){
			yield $name;
		}
	}

	/**
	 * @param $relativeName
	 * @return \Iterator
	 */
	private function walkDir($relativeName){
		/** @var \Directory $d */
		$d = \dir($this->definitionsDir.$relativeName);
		while(false !== ($entry = $d->read())){
			if(!\in_array($entry, ['.', '..'], true)){
				$fullName = $this->definitionsDir.$relativeName.'/'.$entry;
				if(\is_dir($fullName)){
					foreach($this->walkDir($relativeName.'/'.$entry) as $name){
						yield $name;
					}
				}else{
					foreach($this->walkFile($relativeName.'/'.$entry) as $name){
						yield $name;
					}
				}
				$result[] = $entry;
			}
		}
		$d->close();
	}

	/**
	 * @param $relativeFilename
	 * @return \Iterator
	 * @throws \InvalidArgumentException
	 */
	public function walkFile($relativeFilename){
		/** @var array[] $definitions */
		/** @noinspection PhpIncludeInspection */
		$definitions = include $this->definitionsDir.$relativeFilename;
		$deprecated = false;
		foreach($definitions as $defName => $definition){
			$defPath = $relativeFilename.'/'.$defName;
			$namespace = \trim($this->baseNamespace.\str_replace('/', '\\', \substr($relativeFilename, 0, -4)), '\\');
			$className = $namespace.'\\'.$defName;
			$fields = [];
			$interfaces = [];
			$traits = [];
			foreach($definition as $name => $arr){
				if($name[0] !== '@'){
					$fields[] = $this->createFieldDef($name, $arr, $defPath);
				}else{
					$optName = \substr($name, 1);
					switch($optName){
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
			yield new Specification(new ClassName($className), $fields, $interfaces, $traits, $deprecated);
		}
	}

	/**
	 * @param $name
	 * @param $arr
	 * @param $defPath
	 * @return FieldDef
	 * @throws \InvalidArgumentException
	 */
	private function createFieldDef($name, $arr, $defPath){
		if(!\is_array($arr)){
			if(\is_string($arr) || $arr instanceof TypeDef){
				$arr = ['type' => $arr];
			}else{
				throw new \InvalidArgumentException("Bad field definition type at $defPath->{$name}");
			}
		}

		$read_as = [$name];
		if(isset($arr['alias'])){
			$read_as[] = $arr['alias'];
		}
		if(isset($arr['read_as'])){
			$read_as = $arr['read_as'];
			if(!\is_array($read_as)){
				$read_as = [$read_as];
			}
		}

		$write_as = [$name];
		if(isset($arr['write_as'])){
			$write_as = $arr['write_as'];
			if(!\is_array($write_as)){
				$write_as = [$write_as];
			}
		}

		if(\array_key_exists('default', $arr)){
			$has_default = true;
			$default = $arr['default'];
		}else{
			$has_default = false;
			$default = null;
		}
		if(\array_key_exists('empty', $arr)){
			$has_default_serialized = true;
			$default_serialized = $arr['empty'];
		}else{
			$has_default_serialized = false;
			$default_serialized = null;
		}
		return new FieldDef(
			$name,
			$arr['type'],
			$read_as,
			$write_as,
			[$has_default, $default],
			[$has_default_serialized, $default_serialized],
			isset($arr['with']) ? $arr['with'] : false,
			isset($arr['set']) ? $arr['set'] : false,
			isset($arr['merged']) ? $arr['merged'] : false,
			isset($arr['silent']) ? $arr['silent'] : false,
			isset($arr['null']) ? $arr['null'] : ($has_default && $default === null),
			isset($arr['field_visibility']) ? $arr['field_visibility'] : null
		);
	}

}
