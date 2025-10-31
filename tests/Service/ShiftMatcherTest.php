<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\AttendanceManageBundle\Entity\WorkShift;
use Tourze\AttendanceManageBundle\Service\ShiftMatcher;

/**
 * @internal
 */
#[CoversClass(ShiftMatcher::class)]
class ShiftMatcherTest extends TestCase
{
    private ShiftMatcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new ShiftMatcher();
    }

    public function testGetCurrentShiftWithEmptyShifts(): void
    {
        $shifts = [];
        $checkTime = new \DateTimeImmutable('10:00');

        $result = $this->matcher->getCurrentShift($shifts, $checkTime);

        $this->assertNull($result);
    }

    public function testGetCurrentShiftWithInactiveShifts(): void
    {
        $shift = new WorkShift();
        $shift->setGroupId(1);
        $shift->setName('Test Shift');
        $shift->setStartTime(new \DateTimeImmutable('09:00'));
        $shift->setEndTime(new \DateTimeImmutable('17:00'));
        $shift->setActive(false);

        $shifts = [$shift];
        $checkTime = new \DateTimeImmutable('10:00');

        $result = $this->matcher->getCurrentShift($shifts, $checkTime);

        $this->assertNull($result);
    }

    public function testGetCurrentShiftWithRegularShift(): void
    {
        $shift = new WorkShift();
        $shift->setGroupId(1);
        $shift->setName('Regular Shift');
        $shift->setStartTime(new \DateTimeImmutable('09:00'));
        $shift->setEndTime(new \DateTimeImmutable('17:00'));
        $shift->setCrossDay(false);
        $shift->setActive(true);

        $shifts = [$shift];
        $checkTime = new \DateTimeImmutable('10:00');

        $result = $this->matcher->getCurrentShift($shifts, $checkTime);

        $this->assertSame($shift, $result);
    }

    public function testGetCurrentShiftWithCrossDayShift(): void
    {
        $shift = new WorkShift();
        $shift->setGroupId(1);
        $shift->setName('Night Shift');
        $shift->setStartTime(new \DateTimeImmutable('22:00'));
        $shift->setEndTime(new \DateTimeImmutable('06:00'));
        $shift->setCrossDay(true);
        $shift->setActive(true);

        $shifts = [$shift];
        $checkTime = new \DateTimeImmutable('01:00');

        $result = $this->matcher->getCurrentShift($shifts, $checkTime);

        $this->assertSame($shift, $result);
    }

    public function testGetCurrentShiftOutsideTimeRange(): void
    {
        $shift = new WorkShift();
        $shift->setGroupId(1);
        $shift->setName('Day Shift');
        $shift->setStartTime(new \DateTimeImmutable('09:00'));
        $shift->setEndTime(new \DateTimeImmutable('17:00'));
        $shift->setCrossDay(false);
        $shift->setActive(true);

        $shifts = [$shift];
        $checkTime = new \DateTimeImmutable('20:00');

        $result = $this->matcher->getCurrentShift($shifts, $checkTime);

        $this->assertNull($result);
    }
}
