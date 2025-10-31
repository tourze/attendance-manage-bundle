<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AttendanceManageBundle\Enum\ApplicationStatus;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(ApplicationStatus::class)]
class ApplicationStatusTest extends AbstractEnumTestCase
{
    public function testGetColor(): void
    {
        $this->assertSame('warning', ApplicationStatus::PENDING->getColor());
        $this->assertSame('success', ApplicationStatus::APPROVED->getColor());
        $this->assertSame('danger', ApplicationStatus::REJECTED->getColor());
        $this->assertSame('secondary', ApplicationStatus::CANCELLED->getColor());
    }

    public function testIsActive(): void
    {
        $this->assertTrue(ApplicationStatus::PENDING->isActive());
        $this->assertTrue(ApplicationStatus::APPROVED->isActive());
        $this->assertFalse(ApplicationStatus::REJECTED->isActive());
        $this->assertFalse(ApplicationStatus::CANCELLED->isActive());
    }

    public function testCanBeModified(): void
    {
        $this->assertTrue(ApplicationStatus::PENDING->canBeModified());
        $this->assertFalse(ApplicationStatus::APPROVED->canBeModified());
        $this->assertFalse(ApplicationStatus::REJECTED->canBeModified());
        $this->assertFalse(ApplicationStatus::CANCELLED->canBeModified());
    }

    public function testCanBeCancelled(): void
    {
        $this->assertTrue(ApplicationStatus::PENDING->canBeCancelled());
        $this->assertTrue(ApplicationStatus::APPROVED->canBeCancelled());
        $this->assertFalse(ApplicationStatus::REJECTED->canBeCancelled());
        $this->assertFalse(ApplicationStatus::CANCELLED->canBeCancelled());
    }

    public function testToArray(): void
    {
        $array = ApplicationStatus::PENDING->toArray();
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('pending', $array['value']);
        $this->assertEquals('待审批', $array['label']);
    }
}
