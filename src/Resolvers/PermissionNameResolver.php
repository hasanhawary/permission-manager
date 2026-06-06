<?php

namespace HasanHawary\PermissionManager\Resolvers;

use HasanHawary\PermissionManager\Support\PermissionDefinition;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PermissionNameResolver
{
	private OperationResolver $operations;

	public function __construct(OperationResolver $operations)
	{
		$this->operations = $operations;
	}

	public function name(string $modelName, string $operation): string
	{
		return $operation . '-' . $this->group($modelName);
	}

	public function group(string $modelName): string
	{
		return Str::snake(class_basename($modelName), '-');
	}

	public function allForModel(string $modelName): array
	{
		return $this->definitionsForModel($modelName)
			->map(fn(PermissionDefinition $definition) => $definition->name())
			->values()
			->all();
	}

	public function definitionsForModel(string $modelName): Collection
	{
		return collect($this->operations->forModel($modelName))
			->map(fn(string $operation) => new PermissionDefinition(
				$modelName,
				$operation,
				$this->name($modelName, $operation),
				$this->group($modelName)
			))
			->values();
	}

	public function customOperationNames(array $models): array
	{
		return collect($models)->flatMap(function ($operations, $model) {
			return collect($operations)->flatMap(function ($operation) use ($model) {
				if ($operation === 'basic') {
					return collect(OperationResolver::BASIC_OPERATIONS)
						->map(fn(string $op) => $this->name($model, $op));
				}

				if ($operation === '*') {
					return $this->allForModel($model);
				}

				return [$this->name($model, $operation)];
			});
		})->unique()->values()->all();
	}
}
