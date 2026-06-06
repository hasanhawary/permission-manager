<?php

namespace HasanHawary\PermissionManager\Support;

class PermissionDefinition
{
	private string $modelName;

	private string $operation;

	private string $name;

	private string $group;

	public function __construct(string $modelName, string $operation, string $name, string $group)
	{
		$this->modelName = $modelName;
		$this->operation = $operation;
		$this->name = $name;
		$this->group = $group;
	}

	public function modelName(): string
	{
		return $this->modelName;
	}

	public function operation(): string
	{
		return $this->operation;
	}

	public function name(): string
	{
		return $this->name;
	}

	public function group(): string
	{
		return $this->group;
	}
}
