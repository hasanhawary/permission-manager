<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		$tableNames = config('permission.table_names', []);
		$permissionsTable = $tableNames['permissions'] ?? 'permissions';
		$rolesTable = $tableNames['roles'] ?? 'roles';

		if (Schema::hasTable($permissionsTable)) {
			Schema::table($permissionsTable, function (Blueprint $table) use ($permissionsTable) {
				if (!Schema::hasColumn($permissionsTable, 'display_name')) {
					$table->json('display_name')->nullable()->after('guard_name');
				}

				if (!Schema::hasColumn($permissionsTable, 'group')) {
					$table->string('group')->nullable()->after('display_name');
				}
			});
		}

		if (Schema::hasTable($rolesTable)) {
			Schema::table($rolesTable, function (Blueprint $table) use ($rolesTable) {
				if (!Schema::hasColumn($rolesTable, 'display_name')) {
					$table->json('display_name')->nullable()->after('guard_name');
				}

				if (!Schema::hasColumn($rolesTable, 'is_active')) {
					$table->boolean('is_active')->default(true)->after('display_name');
				}
			});
		}
	}

	public function down(): void
	{
		$tableNames = config('permission.table_names', []);
		$permissionsTable = $tableNames['permissions'] ?? 'permissions';
		$rolesTable = $tableNames['roles'] ?? 'roles';

		if (Schema::hasTable($permissionsTable)) {
			Schema::table($permissionsTable, function (Blueprint $table) use ($permissionsTable) {
				if (Schema::hasColumn($permissionsTable, 'group')) {
					$table->dropColumn('group');
				}

				if (Schema::hasColumn($permissionsTable, 'display_name')) {
					$table->dropColumn('display_name');
				}
			});
		}

		if (Schema::hasTable($rolesTable)) {
			Schema::table($rolesTable, function (Blueprint $table) use ($rolesTable) {
				if (Schema::hasColumn($rolesTable, 'is_active')) {
					$table->dropColumn('is_active');
				}

				if (Schema::hasColumn($rolesTable, 'display_name')) {
					$table->dropColumn('display_name');
				}
			});
		}
	}
};
