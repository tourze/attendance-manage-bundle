<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AttendanceManageBundle\Enum\WorkShiftType;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(WorkShiftType::class)]
class WorkShiftTypeTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('fixed', WorkShiftType::FIXED->value);
        $this->assertSame('flexible', WorkShiftType::FLEXIBLE->value);
        $this->assertSame('shift', WorkShiftType::SHIFT->value);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('固定工时', WorkShiftType::FIXED->getLabel());
        $this->assertSame('弹性工时', WorkShiftType::FLEXIBLE->getLabel());
        $this->assertSame('排班制', WorkShiftType::SHIFT->getLabel());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('每天固定上下班时间', WorkShiftType::FIXED->getDescription());
        $this->assertSame('在允许范围内灵活调整上下班时间', WorkShiftType::FLEXIBLE->getDescription());
        $this->assertSame('按排班表工作', WorkShiftType::SHIFT->getDescription());
    }

    public function testGetValue(): void
    {
        $this->assertSame('fixed', WorkShiftType::FIXED->value);
        $this->assertSame('flexible', WorkShiftType::FLEXIBLE->value);
        $this->assertSame('shift', WorkShiftType::SHIFT->value);
    }

    public function testFromValue(): void
    {
        $this->assertSame(WorkShiftType::FIXED, WorkShiftType::from('fixed'));
        $this->assertSame(WorkShiftType::FLEXIBLE, WorkShiftType::from('flexible'));
        $this->assertSame(WorkShiftType::SHIFT, WorkShiftType::from('shift'));
    }

    public function testTryFromInvalidValue(): void
    {
        $this->assertNull(WorkShiftType::tryFrom('invalid'));
    }

    public function testToArray(): void
    {
        $array = WorkShiftType::FIXED->toArray();
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('fixed', $array['value']);
        $this->assertEquals('固定工时', $array['label']);
    }
}
