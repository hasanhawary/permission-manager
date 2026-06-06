<?php

namespace HasanHawary\PermissionManager\Resolvers;

use HasanHawary\PermissionManager\Discovery\ModelDiscovery;
use HasanHawary\PermissionManager\Support\ModelMetadataResolver;
use HasanHawary\PermissionManager\Support\PermissionManagerConfig;

class OperationResolver
{
	public const BASIC_OPERATIONS = ['create', 'read', 'update', 'delete'];

	private PermissionManagerConfig $config;

	private ModelDiscovery $models;

	private ModelMetadataResolver $metadata;

	public function __construct(PermissionManagerConfig $config, ModelDiscovery $models, ?ModelMetadataResolver $metadata = null)
	{
		$this->config = $config;
		$this->models = $models;
		$this->metadata = $metadata ?? new ModelMetadataResolver();
	}

	public function forModel(string $modelName): array
	{
		$operations = self::BASIC_OPERATIONS;
		$subject = $this->models->subjectFor($modelName);

		if (!$subject->isAdditionalOperation()) {
			$operations = $this->metadata->operations($subject, $operations);
		}

		$additionalOperation = collect($this->config->additionalOperations())->firstWhere('name', $modelName);
		if ($additionalOperation) {
			$additionalOperations = $this->normalizeOperations($additionalOperation['operations'] ?? []);
			$operations = !empty($additionalOperation['basic'])
				? array_merge($operations, $additionalOperations)
				: $additionalOperations;
		}

		return $this->normalizeOperations($operations);
	}

	private function normalizeOperations($operations): array
	{
		return collect(is_array($operations) ? $operations : [$operations])
			->filter(fn($operation) => is_string($operation) && trim($operation) !== '')
			->map(fn(string $operation) => trim($operation))
			->unique()
			->values()
			->all();
	}
}
