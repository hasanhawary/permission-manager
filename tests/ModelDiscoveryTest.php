<?php

namespace HasanHawary\PermissionManager\Tests;

use HasanHawary\PermissionManager\Discovery\ModelDiscovery;
use HasanHawary\PermissionManager\Support\PermissionManagerConfig;
use HasanHawary\PermissionManager\Support\PermissionSubject;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class ModelDiscoveryTest extends TestCase
{
	public function test_subject_lookup_uses_resolved_subject_collection(): void
	{
		$discovery = new class(new PermissionManagerConfig()) extends ModelDiscovery {
			private ?Collection $subjects = null;

			protected function allSubjects(): Collection
			{
				if ($this->subjects === null) {
					$this->subjects = collect([new PermissionSubject('Report', 'App\\Models\\Report')]);
				}

				return $this->subjects;
			}
		};

		$this->assertSame('App\\Models\\Report', $discovery->subjectFor('Report')->className());
		$this->assertSame('App\\Models\\Missing', $discovery->subjectFor('Missing')->className());
	}
}
