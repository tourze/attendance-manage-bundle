<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AttendanceManageBundle\Enum\AttendanceStatus;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(AttendanceStatus::class)]
class AttendanceStatusTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        $this->assertEquals('normal', AttendanceStatus::NORMAL->value);
        $this->assertEquals('late', AttendanceStatus::LATE->value);
        $this->assertEquals('early', AttendanceStatus::EARLY->value);
        $this->assertEquals('absent', AttendanceStatus::ABSENT->value);
        $this->assertEquals('leave', AttendanceStatus::LEAVE->value);
        $this->assertEquals('overtime', AttendanceStatus::OVERTIME->value);
        $this->assertEquals('holiday', AttendanceStatus::HOLIDAY->value);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('正常', AttendanceStatus::NORMAL->getLabel());
        $this->assertEquals('迟到', AttendanceStatus::LATE->getLabel());
        $this->assertEquals('早退', AttendanceStatus::EARLY->getLabel());
        $this->assertEquals('旷工', AttendanceStatus::ABSENT->getLabel());
        $this->assertEquals('请假', AttendanceStatus::LEAVE->getLabel());
        $this->assertEquals('加班', AttendanceStatus::OVERTIME->getLabel());
        $this->assertEquals('假期', AttendanceStatus::HOLIDAY->getLabel());
    }

    public function testFromValue(): void
    {
        $status = AttendanceStatus::from('normal');
        $this->assertSame(AttendanceStatus::NORMAL, $status);

        $status = AttendanceStatus::from('late');
        $this->assertSame(AttendanceStatus::LATE, $status);
    }

    public function testTryFromInvalidValue(): void
    {
        $status = AttendanceStatus::tryFrom('invalid');
        $this->assertNull($status);
    }

    public function testToArray(): void
    {
        $array = AttendanceStatus::NORMAL->toArray();
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('normal', $array['value']);
        $this->assertEquals('正常', $array['label']);
    }
}
