<?php

namespace HasanHawary\PermissionManager\Support;

use ReflectionClass;
use ReflectionProperty;
use Throwable;

class ModelMetadataResolver
{
	public function canBeUsed(PermissionSubject $subject): bool
	{
		$object = $this->instantiate($subject);

		if (!$object) {
			return false;
		}

		return (bool) ($object->inPermission ?? true) !== false;
	}

	public function operations(PermissionSubject $subject, array $defaultOperations): array
	{
		$object = $this->instantiate($subject);

		if (!$object || ($object->inPermission ?? true) === false) {
			return $defaultOperations;
		}

		$operations = is_array($object->basicOperations ?? null)
			? $object->basicOperations
			: $defaultOperations;

		if (is_array($object->specialOperations ?? null) && $object->specialOperations !== []) {
			$operations = array_merge($operations, $object->specialOperations);
		}

		return $operations;
	}

	public function guardName(PermissionSubject $subject): ?string
	{
		$object = $this->instantiate($subject);

		if (!$object || !property_exists($object, 'guard_name')) {
			return null;
		}

		try {
			$reflection = new ReflectionProperty($subject->className(), 'guard_name');
			$reflection->setAccessible(true);
			$guardName = $reflection->getValue($object);

			return is_string($guardName) && trim($guardName) !== '' ? trim($guardName) : null;
		} catch (Throwable) {
			return null;
		}
	}

	private function instantiate(PermissionSubject $subject): ?object
	{
		if ($subject->isAdditionalOperation() || !class_exists($subject->className())) {
			return null;
		}

		try {
			$reflection = new ReflectionClass($subject->className());

			if ($reflection->isAbstract() || $reflection->isInterface() || !$reflection->isInstantiable()) {
				return null;
			}

			return $reflection->newInstance();
		} catch (Throwable) {
			return null;
		}
	}
}
