<?php
/* author: Ponomarev Denis <ponomarev@gmail.com> */

namespace dface\CodeGen;

class FieldDef
{

	private string $name;
	/** @var string|TypeDef */
	private $type;
	private ?DefaultDef $constructorDefault;
	private ?DefaultDef $serializedDefault;
	private bool $wither;
	private bool $setter;
	private bool $getter;
	/** @var string[] */
	private array $read_as;
	/** @var string[] */
	private array $write_as;
	private bool $merged;
	private bool $silent;
	private bool $null_able;
	private ?string $field_visibility;

	public function __construct(
		string $name,
		$type,
		array $read_as,
		array $write_as,
		?DefaultDef $constructor_default,
		?DefaultDef $serialized_default,
		bool $wither,
		bool $setter,
		bool $getter,
		bool $merged,
		bool $silent,
		bool $null_able,
		?string $field_visibility
	) {
		$this->name = $name;
		$this->type = $type;
		$this->read_as = $read_as;
		$this->write_as = $write_as;

		$this->constructorDefault = $constructor_default;
		$this->serializedDefault = $serialized_default ?? $constructor_default;
		$this->wither = $wither;
		$this->setter = $setter;
		$this->getter = $getter;
		$this->merged = $merged;
		$this->silent = $silent;
		$this->null_able = $null_able;
		$visibilitySet = ['private', 'protected', 'public', null];
		if (!\in_array($field_visibility, $visibilitySet, true)) {
			throw new \InvalidArgumentException('Fields visibility must be one of ['.implode(', ', $visibilitySet).']');
		}
		$this->field_visibility = $field_visibility;
	}

	public function getName() : string
	{
		return $this->name;
	}

	public function getType()
	{
		return $this->type;
	}

	public function getConstructorDefault() : ?DefaultDef
	{
		return $this->constructorDefault;
	}

	public function getSerializedDefault() : ?DefaultDef
	{
		return $this->serializedDefault;
	}

	public function getWither() : bool
	{
		return $this->wither;
	}

	public function getSetter() : bool
	{
		return $this->setter;
	}

	public function getGetter() : bool
	{
		return $this->getter;
	}

	public function getReadAs() : array
	{
		return $this->read_as;
	}

	public function getWriteAs() : array
	{
		return $this->write_as;
	}

	public function getMerged() : bool
	{
		return $this->merged;
	}

	public function getSilent() : bool
	{
		return $this->silent;
	}

	public function getNullAble() : bool
	{
		return $this->null_able;
	}

	public function getFieldVisibility() : ?string
	{
		return $this->field_visibility;
	}

}
