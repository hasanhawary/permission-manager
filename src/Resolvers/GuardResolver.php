<?php

namespace HasanHawary\PermissionManager\Resolvers;

use HasanHawary\PermissionManager\Discovery\ModelDiscovery;
use HasanHawary\PermissionManager\Support\ModelMetadataResolver;
use HasanHawary\PermissionManager\Support\PermissionManagerConfig;

class GuardResolver
{
	private PermissionManagerConfig $config;

	private ModelDiscovery $models;

	private ModelMetadataResolver $metadata;

	private ?string $overrideGuard = null;

	public function __construct(PermissionManagerConfig $config, ModelDiscovery $models, ?ModelMetadataResolver $metadata = null)
	{
		$this->config = $config;
		$this->models = $models;
		$this->metadata = $metadata ?? new ModelMetadataResolver();
	}

	public function forRole(): string
	{
		return $this->overrideGuard ?? $this->config->defaultGuard();
	}

	public function forModel(string $modelName): string
	{
		if ($this->overrideGuard) {
			return $this->overrideGuard;
		}

		$subject = $this->models->subjectFor($modelName);
		$guardName = $this->metadata->guardName($subject);

		return $guardName ?? $this->config->defaultGuard();
	}

	public function setGuard(string $guard): self
	{
		$this->overrideGuard = $guard;

		return $this;
	}
}
