<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AttendanceManageBundle\Enum\CompensationType;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(CompensationType::class)]
class CompensationTypeTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('paid', CompensationType::PAID->value);
        $this->assertSame('timeoff', CompensationType::TIMEOFF->value);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('加班费', CompensationType::PAID->getLabel());
        $this->assertSame('调休', CompensationType::TIMEOFF->getLabel());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('以现金形式支付加班费', CompensationType::PAID->getDescription());
        $this->assertSame('以调休时间补偿加班', CompensationType::TIMEOFF->getDescription());
    }

    public function testGetValue(): void
    {
        $this->assertSame('paid', CompensationType::PAID->value);
        $this->assertSame('timeoff', CompensationType::TIMEOFF->value);
    }

    public function testFromValue(): void
    {
        $this->assertSame(CompensationType::PAID, CompensationType::from('paid'));
        $this->assertSame(CompensationType::TIMEOFF, CompensationType::from('timeoff'));
    }

    public function testTryFromInvalidValue(): void
    {
        $this->assertNull(CompensationType::tryFrom('invalid'));
    }

    public function testToArray(): void
    {
        $array = CompensationType::PAID->toArray();
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('paid', $array['value']);
        $this->assertEquals('加班费', $array['label']);
    }
}
