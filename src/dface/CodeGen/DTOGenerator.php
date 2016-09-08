<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class DTOGenerator {

	/** @var string */
	private $baseNamespace;
	/** @var string */
	private $definitionsDir;
	/** @var string */
	private $targetSrcRoot;
	/** @var TypeDef[] */
	private $types = [];
	/** @var TypeDef[] */
	private $predefinedTypes;

	public function __construct(
		string $baseNamespace,
		string $definitionDir,
		string $targetSrcRoot,
		array $predefinedTypes
	){
		$this->baseNamespace = $baseNamespace;
		$this->definitionsDir = $definitionDir;
		$this->targetSrcRoot = $targetSrcRoot;
		$this->predefinedTypes = $predefinedTypes;
	}

	function write($relativeName = ''){
		/** @var \Directory $d */
		$d = dir($this->definitionsDir.$relativeName);
		while (false !== ($entry = $d->read())) {
			if(!in_array($entry, ['.', '..'], true)){
				$fullName = $this->definitionsDir.$relativeName.'/'.$entry;
				if(is_dir($fullName)){
					$this->write($relativeName.'/'.$entry);
				}else{
					$this->writeClasses($relativeName.'/'.$entry);
				}
				$result[] = $entry;
			}
		}
		$d->close();
	}

	function writeClasses($relativeFilename){
		$definitions = include $this->definitionsDir.$relativeFilename;
		foreach($definitions as $def_name => $definition){
			$namespace = $this->baseNamespace.str_replace('/', '\\', substr($relativeFilename, 0, -4));
			$data_class_name = $namespace.'\\'.$def_name;
			$data_class_body = $this->generateDataClass($namespace, $def_name, $definition);
			$this->writeClass($data_class_name, $data_class_body);
		}
	}

	private function writeClass($class_name, $class_body){
		$class_filename = $this->targetSrcRoot.'/'.$this->classNameToPsr0Name($class_name);
		@mkdir(dirname($class_filename), 0777, true);
		file_put_contents($class_filename, $class_body);
	}

	private function classNameToPsr0Name($class_name){
		return str_replace('\\', DIRECTORY_SEPARATOR, $class_name).'.php';
	}

	private function generateDataClass($namespace, $def_name, $definition){
		$body = '<?php'."\n\n";
		$body .= "/** Generated class. Don't edit manually. */\n\n";
		$body .= "namespace ".ltrim($namespace, '\\').";\n\n";
		$body .= ($uses = $this->generateUses($namespace, $definition)).($uses ? "\n" : "");
		$body .= "class $def_name implements \\JsonSerializable {\n\n";
		$body .= $this->generateFields($namespace, $definition);
		$body .= "\n";
		$body .= $this->generateConstructor($namespace, $definition);
		$body .= "\n";
		$body .= $this->generateGetters($definition);
		$body .= $this->generateWithers($namespace, $definition);
		$body .= $this->generateSerializerMethod($namespace, $definition);
		$body .= "\n";
		$body .= $this->generateDeserializerMethod($namespace, $definition);
		$body .= "\n";
		$body .= $this->generateFromJsonMethod();
		$body .= "}\n";
		return $body;
	}

	private function generateUses($namespace, $definition){
		$uses = [];
		foreach($definition as $property_name => $property_def){
			$type = $this->getType($namespace, $property_def['type']);
			foreach($type->getUses($namespace) as $u){
				$uses[$u] = "use $u;\n";
			}
		}
		return implode($uses);
	}

	private function generateDeserializerMethod($namespace, $definition){
		$body = "\t"."static function deserialize(\$arr){\n";
		$constructor_args = [];
		foreach($definition as $property_name => $property_def){
			$constructor_args[] = '$'.$property_name;
			$body .= "\t\t"."if(array_key_exists('$property_name', \$arr)){\n";
			$body .= "\t\t\t\$$property_name = \$arr['$property_name'];\n";
			if(isset($property_def['alias'])){
				$alias = $property_def['alias'];
				$body .= "\t\t}elseif(array_key_exists('$alias', \$arr)){\n";
				$body .= "\t\t\t\$$property_name = \$arr['$alias'];\n";
			}
			$body .= "\t\t}else{\n";
			if(array_key_exists('default', $property_def)){
				$body .= "\t\t\t\$$property_name = ".var_export($property_def['default'], true).";\n";
			}else{
				$body .= "\t\t\t"."throw new \\InvalidArgumentException('Property $property_name not specified');\n";
			}
			$body .= "\t\t}\n";
			$type = $this->getType($namespace, $property_def['type']);
			$body .= "\t\t\$$property_name = \$$property_name !== null ? ".$type->getDeserializer('$'.$property_name)." : null;\n\n";
		}
		$body .= "\t\t"."return new self(".implode(", ", $constructor_args).");\n";
		$body .= "\t}\n";
		return $body;
	}

	private function generateFromJsonMethod(){
		$body = "\t"."static function fromJson(\$json_str){\n";
		$body .= "\t\t\$arr = json_decode(\$json_str, true);\n";
		$body .= "\t\t"."if(\$arr === null){\n";
		$body .= "\t\t\t"."throw new \\InvalidArgumentException('Invalid json');\n";
		$body .= "\t\t}\n";
		$body .= "\t\t"."return self::deserialize(\$arr);\n";
		$body .= "\t}\n";
		return $body;
	}

	private function generateSerializerMethod($namespace, $definition){
		$body = "\t"."function jsonSerialize(){\n";
		$body .= "\t\t"."return [\n";
		foreach($definition as $property_name => $property_def){
			$getter = '$this->'.$property_name;
			$type = $this->getType($namespace, $property_def['type']);
			$property_serializer = $type->getSerializer($getter);
			$body .= "\t\t\t"."'$property_name' => $property_serializer,\n";
		}
		$body .= "\t\t"."];\n";
		$body .= "\t}\n";
		return $body;
	}

	private function generateFields($namespace, $definition){
		$body = '';
		foreach($definition as $property_name => $property_def){
			$type = $this->getType($namespace, $property_def['type']);
			$type_hint = $type->getPhpDocHint();
			$body .= "\t/** @var $type_hint */\n";
			$body .= "\t"."protected \$$property_name;\n";
		}
		return $body;
	}

	private function generateConstructor($namespace, $definition){
		$body = '';
		$constructor_params = [];
		$constructor_body = '';
		foreach($definition as $property_name => $property_def){
			$type = $this->getType($namespace, $property_def['type']);
			$type_hint = $type->getArgumentHint();
			$type_hint .= strlen($type_hint) > 0 ? ' ' : '';
			if(array_key_exists('default', $property_def)){
				$def = " = ".var_export($property_def['default'], true);
				if($type instanceof ScalarType){
					$type_hint = '';
				}
			}else{
				$def = "";
			}
			$constructor_params[] = $type_hint.'$'.$property_name.$def;
			$constructor_body .= "\t\t"."\$this->$property_name = \$$property_name;\n";
		}
		if(count($constructor_params) > 3){
			$params_str = "\n\t\t".implode(",\n\t\t", $constructor_params)."\n\t";
		}else{
			$params_str = implode(", ", $constructor_params);
		}
		$body .= "\t"."function __construct(".$params_str."){\n";
		$body .= $constructor_body;
		$body .= "\t}\n";
		return $body;
	}

	private function generateGetters($definition){
		$body = '';
		foreach($definition as $property_name => $property_def){
			$body .= "\t"."function get".$this->camelCase($property_name)."(){\n";
			$body .= "\t\t"."return \$this->$property_name;\n";
			$body .= "\t}\n\n";
		}
		return $body;
	}

	private function generateWithers($namespace, $definition){
		$body = '';
		foreach($definition as $property_name => $property_def){
			if($property_def['with'] ?? false){
				$type = $this->getType($namespace, $property_def['type']);
				$type_hint = $type->getArgumentHint();
				$type_hint .= strlen($type_hint) > 0 ? ' ' : '';
				$body .= "\t"."function with".$this->camelCase($property_name)."($type_hint\$val = null){\n";
				$body .= "\t\t\$clone = clone \$this;\n";
				$body .= "\t\t\$clone->$property_name = \$val;\n";
				$body .= "\t\t"."return \$clone;\n";
				$body .= "\t}\n\n";
			}
		}
		return $body;
	}

	private function camelCase($property_name){
		$camelCase = preg_replace_callback("/_([a-z])/", function ($m){
			return strtoupper($m[1]);
		}, $property_name);
		return strtoupper(substr($camelCase, 0, 1)).substr($camelCase, 1);
	}

	private function getType($namespace, $type_name){
		if(is_array($type_name)){
			$inner_type = $this->getType($namespace, $type_name[0]);
			$type_name = $type_name[0].'[]';
			$full_name = $namespace.'\\'.$type_name;
			if(!isset($this->types[$full_name])){
				$this->types[$full_name] = new ArrayType($inner_type);
			}
		}else{
			$full_name = $namespace.'\\'.$type_name;
			if(!isset($this->types[$full_name])){
				if(isset($this->predefinedTypes[$type_name])){
					$this->types[$full_name] = $this->predefinedTypes[$type_name];
				}else{
					$data_class_name = strpos($type_name, '\\') === false ? ($namespace.'\\'.$type_name) : $type_name;
					$this->types[$full_name] = new DynamicTypeDef($data_class_name);
				}

			}
		}
		return $this->types[$full_name];
	}

}
