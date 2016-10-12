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

	public function __construct(string $baseNamespace, string $definitionsDir, string $relativeName = ''){
		$this->baseNamespace = $baseNamespace;
		$this->definitionsDir = $definitionsDir;
		$this->relativeName = $relativeName;
	}

	public function getIterator(){
		yield from $this->walkDir($this->relativeName);
	}

	private function walkDir($relativeName){
		/** @var \Directory $d */
		$d = dir($this->definitionsDir.$relativeName);
		while (false !== ($entry = $d->read())) {
			if(!in_array($entry, ['.', '..'], true)){
				$fullName = $this->definitionsDir.$relativeName.'/'.$entry;
				if(is_dir($fullName)){
					yield from $this->walkDir($relativeName.'/'.$entry);
				}else{
					yield from $this->walkFile($relativeName.'/'.$entry);
				}
				$result[] = $entry;
			}
		}
		$d->close();
	}

	function walkFile($relativeFilename){
		$definitions = include $this->definitionsDir.$relativeFilename;
		foreach($definitions as $defName => $definition){
			$namespace = trim($this->baseNamespace.str_replace('/', '\\', substr($relativeFilename, 0, -4)), '\\');
			$className = $namespace.'\\'.$defName;
			$fields = [];
			foreach($definition as $name => $arr){
				$aliases = $arr['alias'] ?? [];
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
				$field = new FieldDef($name, $arr['type'], $aliases, $has_default, $default, $arr['with'] ?? false);
				$fields[] = $field;
			}
			yield new Specification(new ClassName($className), $fields);
		}
	}

}
