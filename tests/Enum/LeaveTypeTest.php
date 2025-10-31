<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AttendanceManageBundle\Enum\LeaveType;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(LeaveType::class)]
class LeaveTypeTest extends AbstractEnumTestCase
{
    public function testEnumValuesShouldBeCorrect(): void
    {
        $this->assertEquals('annual', LeaveType::ANNUAL->value);
        $this->assertEquals('sick', LeaveType::SICK->value);
        $this->assertEquals('personal', LeaveType::PERSONAL->value);
        $this->assertEquals('marriage', LeaveType::MARRIAGE->value);
        $this->assertEquals('maternity', LeaveType::MATERNITY->value);
        $this->assertEquals('paternity', LeaveType::PATERNITY->value);
        $this->assertEquals('bereavement', LeaveType::BEREAVEMENT->value);
        $this->assertEquals('compensatory', LeaveType::COMPENSATORY->value);
        $this->assertEquals('unpaid', LeaveType::UNPAID->value);
    }

    public function testGetLabelShouldReturnCorrectChineseLabel(): void
    {
        $this->assertEquals('年假', LeaveType::ANNUAL->getLabel());
        $this->assertEquals('病假', LeaveType::SICK->getLabel());
        $this->assertEquals('事假', LeaveType::PERSONAL->getLabel());
        $this->assertEquals('婚假', LeaveType::MARRIAGE->getLabel());
        $this->assertEquals('产假', LeaveType::MATERNITY->getLabel());
        $this->assertEquals('陪产假', LeaveType::PATERNITY->getLabel());
        $this->assertEquals('丧假', LeaveType::BEREAVEMENT->getLabel());
        $this->assertEquals('调休', LeaveType::COMPENSATORY->getLabel());
        $this->assertEquals('无薪假', LeaveType::UNPAID->getLabel());
    }

    public function testIsPaidShouldReturnTrueForPaidLeaveTypes(): void
    {
        $paidLeaveTypes = [
            LeaveType::ANNUAL,
            LeaveType::SICK,
            LeaveType::MARRIAGE,
            LeaveType::MATERNITY,
            LeaveType::PATERNITY,
            LeaveType::BEREAVEMENT,
            LeaveType::COMPENSATORY,
        ];

        foreach ($paidLeaveTypes as $leaveType) {
            $this->assertTrue($leaveType->isPaid(), "Leave type {$leaveType->value} should be paid");
        }
    }

    public function testIsPaidShouldReturnFalseForUnpaidLeaveTypes(): void
    {
        $unpaidLeaveTypes = [
            LeaveType::PERSONAL,
            LeaveType::UNPAID,
        ];

        foreach ($unpaidLeaveTypes as $leaveType) {
            $this->assertFalse($leaveType->isPaid(), "Leave type {$leaveType->value} should be unpaid");
        }
    }

    public function testEnumShouldBeBackedByString(): void
    {
        $this->assertInstanceOf(\BackedEnum::class, LeaveType::ANNUAL);
        $this->assertIsString(LeaveType::ANNUAL->value);
    }

    public function testAllEnumCasesShouldHaveLabels(): void
    {
        $cases = LeaveType::cases();

        foreach ($cases as $case) {
            $label = $case->getLabel();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    public function testAllEnumCasesShouldHavePaidStatus(): void
    {
        $cases = LeaveType::cases();

        foreach ($cases as $case) {
            $isPaid = $case->isPaid();
            $this->assertIsBool($isPaid);
        }
    }

    public function testEnumCasesShouldHaveExpectedCount(): void
    {
        $cases = LeaveType::cases();
        $this->assertCount(9, $cases);
    }

    public function testEnumFromValueShouldWorkCorrectly(): void
    {
        $this->assertSame(LeaveType::ANNUAL, LeaveType::from('annual'));
        $this->assertSame(LeaveType::SICK, LeaveType::from('sick'));
        $this->assertSame(LeaveType::PERSONAL, LeaveType::from('personal'));
        $this->assertSame(LeaveType::MARRIAGE, LeaveType::from('marriage'));
        $this->assertSame(LeaveType::MATERNITY, LeaveType::from('maternity'));
        $this->assertSame(LeaveType::PATERNITY, LeaveType::from('paternity'));
        $this->assertSame(LeaveType::BEREAVEMENT, LeaveType::from('bereavement'));
        $this->assertSame(LeaveType::COMPENSATORY, LeaveType::from('compensatory'));
        $this->assertSame(LeaveType::UNPAID, LeaveType::from('unpaid'));
    }

    public function testTryFromShouldReturnNullForInvalidValue(): void
    {
        $result = LeaveType::tryFrom('invalid');
        $this->assertNull($result);
    }

    public function testFromShouldThrowExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        LeaveType::from('invalid');
    }

    public function testLeaveTypesShouldBeUniqueValues(): void
    {
        $cases = LeaveType::cases();
        $values = array_map(fn ($case) => $case->value, $cases);
        $uniqueValues = array_unique($values);

        $this->assertCount(count($values), $uniqueValues, 'All enum values should be unique');
    }

    public function testLeaveTypesShouldHaveUniqueLabels(): void
    {
        $cases = LeaveType::cases();
        $labels = array_map(fn ($case) => $case->getLabel(), $cases);
        $uniqueLabels = array_unique($labels);

        $this->assertCount(count($labels), $uniqueLabels, 'All enum labels should be unique');
    }

    public function testPaidAndUnpaidLeaveTypesShouldCoverAllCases(): void
    {
        $cases = LeaveType::cases();
        $paidCount = 0;
        $unpaidCount = 0;

        foreach ($cases as $case) {
            if ($case->isPaid()) {
                ++$paidCount;
            } else {
                ++$unpaidCount;
            }
        }

        $this->assertEquals(count($cases), $paidCount + $unpaidCount, 'All leave types should be either paid or unpaid');
        $this->assertGreaterThan(0, $paidCount, 'There should be at least one paid leave type');
        $this->assertGreaterThan(0, $unpaidCount, 'There should be at least one unpaid leave type');
    }

    public function testToArray(): void
    {
        $array = LeaveType::ANNUAL->toArray();
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('annual', $array['value']);
        $this->assertEquals('年假', $array['label']);
    }
}
