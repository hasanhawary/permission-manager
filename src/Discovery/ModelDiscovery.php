<?php

namespace HasanHawary\PermissionManager\Discovery;

use HasanHawary\PermissionManager\Support\PermissionManagerConfig;
use HasanHawary\PermissionManager\Support\PermissionSubject;
use HasanHawary\PermissionManager\Support\ModelMetadataResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModelDiscovery
{
	private PermissionManagerConfig $config;

	private ModelMetadataResolver $metadata;

	/** @var array<string, PermissionSubject> */
	private array $subjectsByName = [];

	private ?Collection $subjects = null;

	public function __construct(PermissionManagerConfig $config, ?ModelMetadataResolver $metadata = null)
	{
		$this->config = $config;
		$this->metadata = $metadata ?? new ModelMetadataResolver();
	}

	public function subjects(...$exceptions): Collection
	{
		$excluded = collect($exceptions)->filter()->values()->all();

		return $this->allSubjects()
			->reject(fn(PermissionSubject $subject) => in_array($subject->name(), $excluded, true))
			->values();
	}

	public function names(...$exceptions): Collection
	{
		return $this->subjects(...$exceptions)->map(fn(PermissionSubject $subject) => $subject->name());
	}

	public function subjectFor(string $modelName): PermissionSubject
	{
		$this->allSubjects();

		if (isset($this->subjectsByName[$modelName])) {
			return $this->subjectsByName[$modelName];
		}

		return new PermissionSubject($modelName, $this->config->modelsNamespace() . '\\' . $modelName);
	}

	protected function allSubjects(): Collection
	{
		if ($this->subjects !== null) {
			return $this->subjects;
		}

		$this->subjects = $this->generalModelSubjects()
			->merge($this->moduleModelSubjects())
			->merge($this->additionalOperationSubjects())
			->unique(fn(PermissionSubject $subject) => $subject->name())
			->values();
		$this->subjectsByName = $this->subjects
			->mapWithKeys(fn(PermissionSubject $subject) => [$subject->name() => $subject])
			->all();

		return $this->subjects;
	}

	private function generalModelSubjects(): Collection
	{
		$modelsPath = $this->config->modelsPath();

		if (!$modelsPath || !File::isDirectory($modelsPath)) {
			return collect();
		}

		return collect(File::allFiles($modelsPath))
			->filter(fn($file) => $file->getExtension() === 'php')
			->map(function ($file) use ($modelsPath) {
				$relativePath = str_replace(
					[$modelsPath . DIRECTORY_SEPARATOR, '.php'],
					['', ''],
					$file->getRealPath()
				);
					$name = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

					return new PermissionSubject($name, $this->config->modelsNamespace() . '\\' . $name);
				})
			->filter(fn(PermissionSubject $subject) => $this->metadata->canBeUsed($subject))
			->each(fn(PermissionSubject $subject) => $this->remember($subject))
			->values();
	}

	private function moduleModelSubjects(): Collection
	{
		$modulesPath = $this->config->modulesPath();

		if (!$modulesPath || !File::isDirectory($modulesPath)) {
			return collect();
		}

		return collect(File::directories($modulesPath))
			->filter(fn($directory) => !Str::startsWith(basename($directory), '.'))
			->flatMap(function ($moduleDirectory) {
				$modelsPath = $moduleDirectory . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->config->modulesModelsPath());

				if (!File::isDirectory($modelsPath)) {
					return collect();
				}

				$moduleName = basename($moduleDirectory);

				return collect(File::files($modelsPath))
					->filter(fn($file) => Str::endsWith($file->getFilename(), '.php'))
					->map(function ($file) use ($moduleName) {
							$className = $file->getBasename('.php');

							return new PermissionSubject(
								$className,
								$this->config->modulesNamespace() . "\\$moduleName\\" . str_replace('/', '\\', $this->config->modulesModelsPath()) . "\\$className",
								$moduleName
							);
					})
						->filter(fn(PermissionSubject $subject) => $this->metadata->canBeUsed($subject))
					->each(fn(PermissionSubject $subject) => $this->remember($subject));
			})
			->values();
	}

	private function additionalOperationSubjects(): Collection
	{
		return collect($this->config->additionalOperations())
			->pluck('name')
			->filter()
			->map(fn(string $name) => PermissionSubject::additionalOperation($name))
			->each(fn(PermissionSubject $subject) => $this->remember($subject))
			->values();
	}

	private function remember(PermissionSubject $subject): void
	{
		$this->subjectsByName[$subject->name()] = $subject;
	}
}
