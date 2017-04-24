<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class DTOGenerator {

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

	public function __construct(
		\IteratorAggregate $specSource,
		ClassWriter $classWriter,
		array $predefinedTypes,
		$target_version = PHP_VERSION
	){
		$this->specSource = $specSource;
		$this->classWriter = $classWriter;
		$this->predefinedTypes = $predefinedTypes;
		$this->targetVersion = $target_version;
	}

	function generate(){
		foreach($this->specSource as $spec){
			/** @var Specification $spec */
			$code = $this->generateDataClass($spec);
			$this->classWriter->writeClass($spec->getClassName(), $code);
		}
	}

	private function generateDataClass(Specification $spec){
		$namespace = $spec->getClassName()->getNamespace();
		$body = '<?php'."\n\n";
		$body .= "/** Generated class. Don't edit manually. */\n\n";
		if($namespace){
			$body .= 'namespace '.ltrim($namespace, '\\').";\n\n";
		}
		$body .= ($uses = $this->generateUses($spec)).($uses ? "\n" : '');
		$imp = $this->generateImplements($spec);
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

	private function generateUses(Specification $spec){
		$namespace = $spec->getClassName()->getNamespace();
		$uses = [];
		foreach($spec->getFields() as $field){
			$type = $this->getType($namespace, $field->getType());
			foreach($type->getUses($namespace) as $u){
				$u = ltrim($u, '\\');
				$uses[$u] = "use $u;\n";
			}
		}
		foreach($spec->getInterfaces() as $i){
			$fullType = $this->fullTypeName($namespace, $i);
			$className = new ClassName($fullType);
			if($className->getNamespace() !== $namespace){
				$u = ltrim($className->getFullName(), '\\');
				$uses[$u] = "use $u;\n";
			}
		}
		foreach($spec->getTraits() as $i){
			$fullType = $this->fullTypeName($namespace, $i);
			$className = new ClassName($fullType);
			if($className->getNamespace() !== $namespace){
				$u = ltrim($className->getFullName(), '\\');
				$uses[$u] = "use $u;\n";
			}
		}
		return implode($uses);
	}

	private function generateImplements(Specification $spec){
		$namespace = $spec->getClassName()->getNamespace();
		$arr = [];
		foreach($spec->getInterfaces() as $i){
			$fullType = $this->fullTypeName($namespace, $i);
			$className = new ClassName($fullType);
			$iName = ltrim($className->getShortName(), '\\');
			$arr[$iName] = "$iName";
		}
		return $arr ? ', '.implode(', ', $arr) : '';
	}

	private function generateTraits(Specification $spec){
		$namespace = $spec->getClassName()->getNamespace();
		$arr = [];
		foreach($spec->getTraits() as $i){
			$fullType = $this->fullTypeName($namespace, $i);
			$className = new ClassName($fullType);
			$tName = ltrim($className->getShortName(), '\\');
			$arr[$tName] = "$tName";
		}
		return $arr ? ("\t".'use '.implode(";\n\t".'use ', $arr).";\n\n") : '';
	}

	private function generateDeserializerMethod(Specification $spec){
		$namespace = $spec->getClassName()->getNamespace();
		$ret_hint = '';
		$support_ret_hint = version_compare($this->targetVersion, '7.1') >= 0;
		if($support_ret_hint){
			 $ret_hint = ' : '.$spec->getClassName()->getShortName().' ';
		}
		$body = "\t/**\n";
		$body .= "\t * @param array \$arr\n";
		$body .= "\t * @return self\n";
		$body .= "\t * @throws \\InvalidArgumentException\n";
		$body .= "\t */\n";
		$body .= "\t"."static function deserialize(\$arr)$ret_hint{\n";
		$constructor_args = [];
		foreach($spec->getFields() as $field){
			$property_name = $field->getName();
			$constructor_args[] = '$'.$property_name;
			if($field->getMerged()){
				$body .= "\t\t\$$property_name = \$arr;\n";
			}else{
				$has_def = $field->hasSerializedDefault();
				if($has_def){
					$body .= "\t\t\$$property_name = ".$this->varExport($field->getSerializedDefault()).";\n";
				}
				$body .= "\t\t"."if(array_key_exists('$property_name', \$arr)){\n";
				$body .= "\t\t\t\$$property_name = \$arr['$property_name'];\n";
				foreach($field->getAliases() as $alias){
					$body .= "\t\t}elseif(array_key_exists('$alias', \$arr)){\n";
					$body .= "\t\t\t\$$property_name = \$arr['$alias'];\n";
				}
				if(!$has_def){
					$body .= "\t\t}else{\n";
					$body .= "\t\t\t"."throw new \\InvalidArgumentException(\"Property '$property_name' not specified\");\n";
				}
				$body .= "\t\t}\n";
			}
			$type = $this->getType($namespace, $field->getType());
			$deserializer = $type->getDeserializer('$'.$property_name, "\t\t");
			if($deserializer !== "\$$property_name"){
				$body .= "\t\t\$$property_name = ".$deserializer.";\n\n";
			}
		}
		$body .= "\t\t".'return new static('.implode(', ', $constructor_args).");\n";
		$body .= "\t}\n";
		return $body;
	}

	private function generateSerializerMethod(Specification $spec){
		$namespace = $spec->getClassName()->getNamespace();
		$body = "\t"."function jsonSerialize(){\n";
		$prop_body = '';
		$merge = [];
		foreach($spec->getFields() as $field){
			$property_name = $field->getName();
			$getter = '$this->'.$property_name;
			$type = $this->getType($namespace, $field->getType());
			$property_serializer = $type->getSerializer($getter, "\t\t\t");
			if($field->getMerged()){
				$merge["\$merge_${property_name}"] .= "\t\t"."\$merge_${property_name} = $property_serializer;\n";
			}else{
				$prop_body .= "\t\t\t"."'$property_name' => $property_serializer,\n";
			}
		}
		if(!$merge){
			$body .= "\t\t"."return [\n";
			$body .= $prop_body;
			$body .= "\t\t"."];\n";
		}else{
			$body .= "\t\t"."\$result = [\n";
			$body .= $prop_body;
			$body .= "\t\t"."];\n";
			$body .= implode('', $merge);
			$body .= "\t\t".'return array_replace($result, '.implode(', ', array_keys($merge))." ?: []);\n";
		}
		$body .= "\t}\n";
		return $body;
	}

	private function generateFields(Specification $spec){
		$namespace = $spec->getClassName()->getNamespace();
		$body = '';
		foreach($spec->getFields() as $field){
			$property_name = $field->getName();
			$type = $this->getType($namespace, $field->getType());
			$type_hint = $type->getPhpDocHint();
			$body .= "\t/** @var $type_hint */\n";
			$body .= "\t"."protected \$$property_name;\n";
		}
		return $body;
	}

	private function generateConstructor(Specification $spec){
		$namespace = $spec->getClassName()->getNamespace();
		$body = '';
		$constructor_params = [];
		$constructor_body = '';
		$hint_scalars = version_compare($this->targetVersion, '7.0') >= 0;
		$hint_nulls = version_compare($this->targetVersion, '7.1') >= 0;
		foreach($spec->getFields() as $field){
			$property_name = $field->getName();
			$type = $this->getType($namespace, $field->getType());
			$type_hint = $type->getArgumentHint();
			$is_scalar = $type instanceof ScalarType;
			$type_hint .= strlen($type_hint) > 0 ? ' ' : '';
			if($hint_nulls && $field->getNullAble() && $type_hint){
				$type_hint = '?'.$type_hint;
			}
			$has_def = $field->hasConstructorDefault();
			$def = '';
			if($has_def){
				$def = ' = '.$this->varExport($field->getConstructorDefault());
			}
			$right_val = "\$$property_name";
			if($is_scalar){
				if(!$hint_scalars || $has_def){
					$type_hint = '';
				}
				if(!$hint_scalars){
					if($field->getNullAble()){
						$right_val =  "$right_val === null ? null : (".$type->getArgumentHint().") $right_val";
					}else{
						$right_val = '('.$type->getArgumentHint().") $right_val";
					}
				}
			}
			$constructor_params[] = $type_hint.'$'.$property_name.$def;
			$constructor_body .= "\t\t"."\$this->$property_name = $right_val;\n";
		}
		if(count($constructor_params) > 3){
			$params_str = "\n\t\t".implode(",\n\t\t", $constructor_params)."\n\t";
		}else{
			$params_str = implode(', ', $constructor_params);
		}
		$body .= "\t".'function __construct('.$params_str."){\n";
		$body .= $constructor_body;
		$body .= "\t}\n";
		return $body;
	}

	private function generateGetters(Specification $spec){
		$namespace = $spec->getClassName()->getNamespace();
		$body = '';
		$support_ret_hint = version_compare($this->targetVersion, '7.1') >= 0;
		foreach($spec->getFields() as $field){
			$type = $this->getType($namespace, $field->getType());
			$doc_hint = $type->getPhpDocHint();
			$body .= "\t/**\n";
			$body .= "\t * @return $doc_hint \$val\n";
			$body .= "\t */\n";
			$property_name = $field->getName();
			$ret_hint = '';
			if($support_ret_hint && ($arg_hint = $type->getArgumentHint())){
				if($field->getNullAble()){
					$arg_hint = '?'.$arg_hint;
				}
				$ret_hint = ' : '.$arg_hint.' ';
			}
			$body .= "\t".'function get'.$this->camelCase($property_name)."()$ret_hint{\n";
			$body .= "\t\t"."return \$this->$property_name;\n";
			$body .= "\t}\n\n";
		}
		return $body;
	}

	private function generateSetters(Specification $spec){
		$namespace = $spec->getClassName()->getNamespace();
		$body = '';
		foreach($spec->getFields() as $field){
			$property_name = $field->getName();
			if($field->getSetter()){
				$type = $this->getType($namespace, $field->getType());
				$doc_hint = $type->getPhpDocHint();
				$type_hint = $type->getArgumentHint();
				$type_hint .= strlen($type_hint) > 0 ? ' ' : '';
				$body .= "\t/**\n";
				$body .= "\t * @param $doc_hint \$val\n";
				$body .= "\t */\n";
				$body .= "\t".'function set'.$this->camelCase($property_name)."($type_hint\$val = null){\n";
				$body .= "\t\t\$this->$property_name = \$val;\n";
				$body .= "\t}\n\n";
			}
		}
		return $body;
	}

	private function generateWithers(Specification $spec){
		$namespace = $spec->getClassName()->getNamespace();
		$body = '';
		$hint_scalars = version_compare($this->targetVersion, '7.0') >= 0;
		$ret_hint = version_compare($this->targetVersion, '7.1') >= 0 ? ' : self ' : '';
		foreach($spec->getFields() as $field){
			$property_name = $field->getName();
			if($field->getWither()){
				$type = $this->getType($namespace, $field->getType());
				$doc_hint = $type->getPhpDocHint();
				$type_hint = $type->getArgumentHint();
				if($type instanceof ScalarType && !$hint_scalars){
					$type_hint = '';
				}
				$type_hint .= strlen($type_hint) > 0 ? ' ' : '';
				$body .= "\t/**\n";
				$body .= "\t * @param $doc_hint \$val\n";
				$body .= "\t * @return self\n";
				$body .= "\t */\n";
				$body .= "\t".'function with'.$this->camelCase($property_name)."($type_hint\$val = null)$ret_hint{\n";
				$body .= "\t\t\$clone = clone \$this;\n";
				$body .= "\t\t\$clone->$property_name = \$val;\n";
				$body .= "\t\t"."return \$clone;\n";
				$body .= "\t}\n\n";
			}
		}
		return $body;
	}

	private function camelCase($property_name){
		$camelCase = preg_replace_callback('/_([a-z])/', function ($m){
			return strtoupper($m[1]);
		}, $property_name);
		return strtoupper($camelCase[0]).substr($camelCase, 1);
	}

	private function fullTypeName($namespace, $type_name){
		return strpos($type_name, '\\') === false ? $namespace.'\\'.$type_name : $type_name;
	}

	private function getType($namespace, $type_name){
		if($type_name instanceof TypeDef){
			return $type_name;
		}
		if(is_array($type_name)){
			$type_name = $type_name[0].'[]';
		}
		if(isset($this->predefinedTypes[$type_name])){
			return $this->predefinedTypes[$type_name];
		}
		$full_name = $this->fullTypeName($namespace, $type_name);
		if(!isset($this->types[$full_name])){
			if(substr($type_name, -2) === '[]'){
				$el_type = substr($type_name, 0, -2);
				if($el_type === ''){
					throw new \InvalidArgumentException('Specify element type');
				}
				$inner_type = $this->getType($namespace, $el_type);
				$this->types[$full_name] = new ArrayType($inner_type);
			}elseif(substr($type_name, -2) === '{}'){
				$el_type = substr($type_name, 0, -2);
				if($el_type === ''){
					throw new \InvalidArgumentException('Specify element type');
				}
				$inner_type = $this->getType($namespace, $el_type);
				$this->types[$full_name] = new MapType($inner_type);
			}elseif(is_a($full_name, TypeDef::class)){
				$this->types[$full_name] = new $full_name;
			}else{
				$this->types[$full_name] = new DynamicTypeDef(new ClassName($full_name));
			}
		}
		return $this->types[$full_name];
	}

	private function varExport($var){
		if($var === null){
			return 'null';
		}
		if($var === []){
			return '[]';
		}
		return var_export($var, true);
	}

}
