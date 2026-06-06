<?php

namespace HasanHawary\PermissionManager\Services;

use Illuminate\Support\Facades\DB;

class PermissionResetter
{
	public function reset(): void
	{
		$this->withoutForeignKeyChecks(function () {
			foreach ($this->tables() as $table) {
				DB::table($table)->truncate();
			}
		});
	}

	public function tables(): array
	{
		return [
			$this->tableName('model_has_permissions'),
			$this->tableName('model_has_roles'),
			$this->tableName('role_has_permissions'),
			$this->tableName('permissions'),
			$this->tableName('roles'),
		];
	}

	private function withoutForeignKeyChecks(callable $callback): void
	{
		$driver = DB::connection()->getDriverName();
		$foreignKeysDisabled = false;

		try {
			if (in_array($driver, ['mysql', 'mariadb'], true)) {
				DB::statement('SET FOREIGN_KEY_CHECKS=0;');
				$foreignKeysDisabled = true;
			}

			$callback();
		} finally {
			if ($foreignKeysDisabled) {
				DB::statement('SET FOREIGN_KEY_CHECKS=1;');
			}
		}
	}

	protected function tableName(string $default): string
	{
		return (string) config("permission.table_names.$default", $default);
	}
}
