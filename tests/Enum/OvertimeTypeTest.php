<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AttendanceManageBundle\Enum\OvertimeType;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(OvertimeType::class)]
class OvertimeTypeTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('workday', OvertimeType::WORKDAY->value);
        $this->assertSame('weekend', OvertimeType::WEEKEND->value);
        $this->assertSame('holiday', OvertimeType::HOLIDAY->value);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('工作日加班', OvertimeType::WORKDAY->getLabel());
        $this->assertSame('周末加班', OvertimeType::WEEKEND->getLabel());
        $this->assertSame('节假日加班', OvertimeType::HOLIDAY->getLabel());
    }

    public function testGetMultiplier(): void
    {
        $this->assertSame(1.5, OvertimeType::WORKDAY->getMultiplier());
        $this->assertSame(2.0, OvertimeType::WEEKEND->getMultiplier());
        $this->assertSame(3.0, OvertimeType::HOLIDAY->getMultiplier());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('工作日加班', OvertimeType::WORKDAY->getLabel());
        $this->assertSame('周末加班', OvertimeType::WEEKEND->getLabel());
        $this->assertSame('节假日加班', OvertimeType::HOLIDAY->getLabel());
    }

    public function testGetValue(): void
    {
        $this->assertSame('workday', OvertimeType::WORKDAY->value);
        $this->assertSame('weekend', OvertimeType::WEEKEND->value);
        $this->assertSame('holiday', OvertimeType::HOLIDAY->value);
    }

    public function testFromValue(): void
    {
        $this->assertSame(OvertimeType::WORKDAY, OvertimeType::from('workday'));
        $this->assertSame(OvertimeType::WEEKEND, OvertimeType::from('weekend'));
        $this->assertSame(OvertimeType::HOLIDAY, OvertimeType::from('holiday'));
    }

    public function testTryFromInvalidValue(): void
    {
        $this->assertNull(OvertimeType::tryFrom('invalid'));
    }

    public function testToArray(): void
    {
        $array = OvertimeType::WORKDAY->toArray();
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('workday', $array['value']);
        $this->assertEquals('工作日加班', $array['label']);
    }
}
