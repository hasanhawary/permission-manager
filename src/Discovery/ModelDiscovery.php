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

        if (!$modelsPath) {
            return collect();
        }

        if (!File::isDirectory($modelsPath)) {
            // Try to find the directory case-insensitively for Linux compatibility
            $basePath = base_path();
            $relativeModelsPath = str_replace($basePath, '', $modelsPath);
            $parts = explode(DIRECTORY_SEPARATOR, trim($relativeModelsPath, DIRECTORY_SEPARATOR));

            $parentDir = $basePath;
            foreach ($parts as $part) {
                if (!File::isDirectory($parentDir)) {
                    $parentDir = null;
                    break;
                }

                $found = collect(File::directories($parentDir))
                    ->first(fn($dir) => strtolower(basename($dir)) === strtolower($part));

                if ($found) {
                    $parentDir = $found;
                } else {
                    $parentDir = null;
                    break;
                }
            }

            if ($parentDir && File::isDirectory($parentDir)) {
                $modelsPath = $parentDir;
            } else {
                return collect();
            }
        }

        return collect(File::allFiles($modelsPath))
            ->filter(fn($file) => $file->getExtension() === 'php')
            ->map(function ($file)  {
                $name = $file->getBasename('.php');

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
                $modulesModelsPath = $this->config->modulesModelsPath();
                $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $modulesModelsPath);

                $modelsPath = $moduleDirectory . DIRECTORY_SEPARATOR . $normalizedPath;

                if (!File::isDirectory($modelsPath)) {
                    // Try to find the directory case-insensitively for Linux compatibility
                    $parentDir = $moduleDirectory;
                    $parts = explode(DIRECTORY_SEPARATOR, trim($normalizedPath, DIRECTORY_SEPARATOR));

                    foreach ($parts as $part) {
                        if (!File::isDirectory($parentDir)) {
                            $parentDir = null;
                            break;
                        }

                        $found = collect(File::directories($parentDir))
                            ->first(fn($dir) => strtolower(basename($dir)) === strtolower($part));

                        if ($found) {
                            $parentDir = $found;
                        } else {
                            $parentDir = null;
                            break;
                        }
                    }

                    if ($parentDir && File::isDirectory($parentDir)) {
                        $modelsPath = $parentDir;
                    } else {
                        return collect();
                    }
                }

                $moduleName = basename($moduleDirectory);
                // Convert path separators to backslashes for namespace (PHP namespaces always use backslashes)
                $namespacePath = str_replace([DIRECTORY_SEPARATOR, '/'], '\\', $modulesModelsPath);

                return collect(File::files($modelsPath))
                    ->filter(fn($file) => $file->getExtension() === 'php')
                    ->map(function ($file) use ($moduleName, $namespacePath) {
                        $className = $file->getBasename('.php');

                        return new PermissionSubject(
                            $className,
                            $this->config->modulesNamespace() . "\\$moduleName\\" . $namespacePath . "\\$className",
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
