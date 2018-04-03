<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class DateTimeType implements TypeDef {

	/** @var string */
	private $serializeFormat;

	public function __construct($serializeFormat){
		$this->serializeFormat = $serializeFormat;
	}

	function getUses($namespace){
		return [\DateTimeImmutable::class];
	}

	function getSerializer($value_expression, $null_able, $indent){
		return ($null_able ? "$value_expression === null ? null : " : '').$value_expression."->format('$this->serializeFormat')";
	}

	function getDeserializer($target, $value_expression, $indent){
		$body = "try {\n";
		$body .= $indent."\t"."$target = $value_expression !== null ? new DateTimeImmutable($value_expression) : null;\n";
		$body .= $indent."}catch (\Exception \$e){\n";
		$body .= $indent."\t"."throw new \\InvalidArgumentException(\$e->getMessage(), 0, \$e);\n";
		$body .= $indent."}\n";
		return $body;
	}

	function getArgumentHint(){
		return 'DateTimeImmutable';
	}

	function getPhpDocHint(){
		return 'DateTimeImmutable';
	}

}
