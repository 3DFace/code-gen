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

	private function walkDir($relativeName){
		/** @var \Directory $d */
		$d = dir($this->definitionsDir.$relativeName);
		while (false !== ($entry = $d->read())) {
			if(!in_array($entry, ['.', '..'], true)){
				$fullName = $this->definitionsDir.$relativeName.'/'.$entry;
				if(is_dir($fullName)){
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

	function walkFile($relativeFilename){
		$definitions = include $this->definitionsDir.$relativeFilename;
		foreach($definitions as $defName => $definition){
			$defPath = $relativeFilename.'/'.$defName;
			$namespace = trim($this->baseNamespace.str_replace('/', '\\', substr($relativeFilename, 0, -4)), '\\');
			$className = $namespace.'\\'.$defName;
			$fields = [];
			$iArr = [];
			foreach($definition as $name => $arr){
				if(substr($name, 0, 1) !== '@'){
					$fields[] = $this->createFieldDef($name, $arr, $defPath);
				}else{
					$optName = substr($name, 1);
					switch($optName){
						case 'implements':
							$iArr = is_array($arr) ? $arr: [$arr];
							break;
						default:
							throw new \InvalidArgumentException("Unsupported option $optName");
					}
				}
			}
			yield new Specification(new ClassName($className), $fields, $iArr);
		}
	}

	private function createFieldDef($name, $arr, $defPath){
		if(!is_array($arr)){
			if(is_string($arr)){
				$arr = ['type' => $arr];
			}else{
				throw new \InvalidArgumentException("Bad field definition type at $defPath->{$name}");
			}
		}
		$aliases = isset($arr['alias']) ? $arr['alias'] : [];
		if(!is_array($aliases)){
			$aliases = [$aliases];
		}
		if(array_key_exists('default', $arr)){
			$has_default = true;
			$default = $arr['default'];
		}else{
			$has_default = false;
			$default = null;
		}
		return new FieldDef($name, $arr['type'], $aliases, $has_default, $default, isset($arr['with']) ? $arr['with'] : false);
	}

}
