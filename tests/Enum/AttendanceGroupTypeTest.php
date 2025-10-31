<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tourze\AttendanceManageBundle\Enum\AttendanceGroupType;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(AttendanceGroupType::class)]
class AttendanceGroupTypeTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('fixed', AttendanceGroupType::FIXED->value);
        $this->assertSame('flexible', AttendanceGroupType::FLEXIBLE->value);
        $this->assertSame('shift', AttendanceGroupType::SHIFT->value);
    }

    #[DataProvider('labelProvider')]
    public function testGetLabel(AttendanceGroupType $type, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $type->getLabel());
    }

    /**
     * @return iterable<string, array{AttendanceGroupType, string}>
     */
    public static function labelProvider(): iterable
    {
        return [
            'fixed' => [AttendanceGroupType::FIXED, '固定时间'],
            'flexible' => [AttendanceGroupType::FLEXIBLE, '弹性时间'],
            'shift' => [AttendanceGroupType::SHIFT, '轮班制'],
        ];
    }

    #[DataProvider('colorProvider')]
    public function testGetColor(AttendanceGroupType $type, string $expectedColor): void
    {
        $this->assertSame($expectedColor, $type->getColor());
    }

    /**
     * @return iterable<string, array{AttendanceGroupType, string}>
     */
    public static function colorProvider(): iterable
    {
        return [
            'fixed' => [AttendanceGroupType::FIXED, 'primary'],
            'flexible' => [AttendanceGroupType::FLEXIBLE, 'success'],
            'shift' => [AttendanceGroupType::SHIFT, 'warning'],
        ];
    }

    #[DataProvider('typeCheckProvider')]
    public function testIsFixedTime(AttendanceGroupType $type, bool $expected): void
    {
        $this->assertSame($expected, $type->isFixedTime());
    }

    /**
     * @return iterable<string, array{AttendanceGroupType, bool}>
     */
    public static function typeCheckProvider(): iterable
    {
        return [
            'fixed is fixed' => [AttendanceGroupType::FIXED, true],
            'flexible is not fixed' => [AttendanceGroupType::FLEXIBLE, false],
            'shift is not fixed' => [AttendanceGroupType::SHIFT, false],
        ];
    }

    #[DataProvider('flexibleTimeProvider')]
    public function testIsFlexibleTime(AttendanceGroupType $type, bool $expected): void
    {
        $this->assertSame($expected, $type->isFlexibleTime());
    }

    /**
     * @return iterable<string, array{AttendanceGroupType, bool}>
     */
    public static function flexibleTimeProvider(): iterable
    {
        return [
            'fixed is not flexible' => [AttendanceGroupType::FIXED, false],
            'flexible is flexible' => [AttendanceGroupType::FLEXIBLE, true],
            'shift is not flexible' => [AttendanceGroupType::SHIFT, false],
        ];
    }

    #[DataProvider('shiftWorkProvider')]
    public function testIsShiftWork(AttendanceGroupType $type, bool $expected): void
    {
        $this->assertSame($expected, $type->isShiftWork());
    }

    /**
     * @return iterable<string, array{AttendanceGroupType, bool}>
     */
    public static function shiftWorkProvider(): iterable
    {
        return [
            'fixed is not shift' => [AttendanceGroupType::FIXED, false],
            'flexible is not shift' => [AttendanceGroupType::FLEXIBLE, false],
            'shift is shift' => [AttendanceGroupType::SHIFT, true],
        ];
    }

    public function testGetSelectChoices(): void
    {
        $choices = AttendanceGroupType::getSelectChoices();

        $this->assertIsArray($choices);
        $this->assertCount(3, $choices);

        // 检查键值对
        $this->assertArrayHasKey('固定时间', $choices);
        $this->assertArrayHasKey('弹性时间', $choices);
        $this->assertArrayHasKey('轮班制', $choices);

        $this->assertSame('fixed', $choices['固定时间']);
        $this->assertSame('flexible', $choices['弹性时间']);
        $this->assertSame('shift', $choices['轮班制']);
    }

    public function testAllCasesAreCovered(): void
    {
        $allCases = AttendanceGroupType::cases();
        $this->assertCount(3, $allCases);

        $values = array_map(fn ($case) => $case->value, $allCases);
        $this->assertContains('fixed', $values);
        $this->assertContains('flexible', $values);
        $this->assertContains('shift', $values);
    }

    public function testToArray(): void
    {
        // 测试 toArray() 方法（通过 ItemTrait 提供）
        // toArray() 是实例方法，返回包含 value 和 label 的关联数组
        $array = AttendanceGroupType::FIXED->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertSame('fixed', $array['value']);
        $this->assertSame('固定时间', $array['label']);

        // 测试其他枚举值
        $flexibleArray = AttendanceGroupType::FLEXIBLE->toArray();
        $this->assertSame('flexible', $flexibleArray['value']);
        $this->assertSame('弹性时间', $flexibleArray['label']);

        $shiftArray = AttendanceGroupType::SHIFT->toArray();
        $this->assertSame('shift', $shiftArray['value']);
        $this->assertSame('轮班制', $shiftArray['label']);
    }
}
