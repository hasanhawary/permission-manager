<?php

namespace HasanHawary\PermissionManager\Support;

class PermissionSubject
{
	private string $name;

	private string $className;

	private ?string $moduleName;

	private bool $additionalOperation;

	public function __construct(string $name, string $className, ?string $moduleName = null, bool $additionalOperation = false)
	{
		$this->name = $name;
		$this->className = $className;
		$this->moduleName = $moduleName;
		$this->additionalOperation = $additionalOperation;
	}

	public static function additionalOperation(string $name): self
	{
		return new self($name, $name, null, true);
	}

	public function name(): string
	{
		return $this->name;
	}

	public function className(): string
	{
		return $this->className;
	}

	public function moduleName(): ?string
	{
		return $this->moduleName;
	}

	public function isAdditionalOperation(): bool
	{
		return $this->additionalOperation;
	}
}
