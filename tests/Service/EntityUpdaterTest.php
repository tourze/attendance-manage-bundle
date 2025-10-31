<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\AttendanceManageBundle\Entity\AttendanceGroup;
use Tourze\AttendanceManageBundle\Entity\WorkShift;
use Tourze\AttendanceManageBundle\Enum\AttendanceGroupType;
use Tourze\AttendanceManageBundle\Service\EntityUpdater;

/**
 * @internal
 */
#[CoversClass(EntityUpdater::class)]
class EntityUpdaterTest extends TestCase
{
    private EntityUpdater $updater;

    protected function setUp(): void
    {
        $this->updater = new EntityUpdater();
    }

    public function testUpdateAttendanceGroupWithAllFields(): void
    {
        $group = $this->createMock(AttendanceGroup::class);

        $group->expects($this->once())->method('setName')->with('Test Group');
        $group->expects($this->once())->method('setType')->with(AttendanceGroupType::FIXED);
        $group->expects($this->once())->method('setRules')->with(['rule1']);
        $group->expects($this->once())->method('setMemberIds')->with([1, 2, 3]);
        $group->expects($this->once())->method('setActive')->with(true);

        $this->updater->updateAttendanceGroup($group, [
            'name' => 'Test Group',
            'type' => 'fixed',
            'rules' => ['rule1'],
            'memberIds' => [1, 2, 3],
            'isActive' => true,
        ]);
    }

    public function testUpdateAttendanceGroupWithPartialFields(): void
    {
        $group = $this->createMock(AttendanceGroup::class);

        $group->expects($this->once())->method('setName')->with('New Name');
        $group->expects($this->never())->method('setType');

        $this->updater->updateAttendanceGroup($group, [
            'name' => 'New Name',
        ]);
    }

    public function testUpdateWorkShiftWithAllFields(): void
    {
        $shift = $this->createMock(WorkShift::class);
        $startTime = new \DateTimeImmutable('09:00');
        $endTime = new \DateTimeImmutable('17:00');

        $shift->expects($this->once())->method('setName')->with('Test Shift');
        $shift->expects($this->once())->method('setStartTime')->with($startTime);
        $shift->expects($this->once())->method('setEndTime')->with($endTime);
        $shift->expects($this->once())->method('setFlexibleMinutes')->with(30);
        $shift->expects($this->once())->method('setBreakTimes')->with([]);
        $shift->expects($this->once())->method('setCrossDay')->with(false);
        $shift->expects($this->once())->method('setActive')->with(true);

        $this->updater->updateWorkShift($shift, [
            'name' => 'Test Shift',
            'startTime' => $startTime,
            'endTime' => $endTime,
            'flexibleMinutes' => 30,
            'breakTimes' => [],
            'crossDay' => false,
            'isActive' => true,
        ]);
    }
}
