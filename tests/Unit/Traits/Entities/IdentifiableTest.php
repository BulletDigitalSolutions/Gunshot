<?php

declare(strict_types=1);

namespace BulletDigitalSolutions\Gunshot\Tests\Unit\Traits\Entities;

use BulletDigitalSolutions\Gunshot\Traits\Entities\Identifiable;
use PHPUnit\Framework\TestCase;

final class IdentifiableTest extends TestCase
{
    public function test_assign_uuid_generates_a_uuid_string(): void
    {
        $entity = new class {
            use Identifiable;
        };

        $entity->assignUuid();

        $uuid = $entity->getUuid();

        $this->assertIsString($uuid);
        $this->assertSame(36, strlen($uuid));
    }

    public function test_uuid_setter_and_getter_work(): void
    {
        $entity = new class {
            use Identifiable;
        };

        $entity->setUuid('123e4567-e89b-12d3-a456-426614174000');

        $this->assertSame('123e4567-e89b-12d3-a456-426614174000', $entity->getUuid());
    }
}
