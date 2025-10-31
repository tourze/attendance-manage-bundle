<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\AttendanceManageBundle\Entity\AttendanceGroup;
use Tourze\AttendanceManageBundle\Entity\AttendanceRecord;
use Tourze\AttendanceManageBundle\Entity\HolidayConfig;
use Tourze\AttendanceManageBundle\Entity\LeaveApplication;
use Tourze\AttendanceManageBundle\Entity\OvertimeApplication;
use Tourze\AttendanceManageBundle\Entity\WorkShift;
use Tourze\AttendanceManageBundle\Enum\AttendanceGroupType;
use Tourze\AttendanceManageBundle\Enum\AttendanceStatus;
use Tourze\AttendanceManageBundle\Enum\CheckInType;
use Tourze\AttendanceManageBundle\Enum\CompensationType;
use Tourze\AttendanceManageBundle\Enum\LeaveType;
use Tourze\AttendanceManageBundle\Enum\OvertimeType;

/**
 * 考勤管理模块测试数据
 *
 * @internal
 */
final class TestSchemaFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 创建考勤组
        $this->createAttendanceGroups($manager);

        // 创建工作班次
        $this->createWorkShifts($manager);

        // 创建考勤记录
        $this->createAttendanceRecords($manager);

        // 创建请假申请
        $this->createLeaveApplications($manager);

        // 创建加班申请
        $this->createOvertimeApplications($manager);

        // 创建假期配置
        $this->createHolidayConfigs($manager);

        $manager->flush();
    }

    private function createAttendanceGroups(ObjectManager $manager): void
    {
        $group1 = new AttendanceGroup();
        $group1->setName('标准考勤组');
        $group1->setType(AttendanceGroupType::FIXED);
        $group1->setRules([
            'start_time' => '09:00',
            'end_time' => '17:00',
            'break_duration' => 60,
            'flexible_minutes' => 30,
        ]);
        $group1->setMemberIds([1, 2, 3, 4, 5]);

        $group2 = new AttendanceGroup();
        $group2->setName('弹性考勤组');
        $group2->setType(AttendanceGroupType::FLEXIBLE);
        $group2->setRules([
            'start_earliest' => '08:00',
            'start_latest' => '10:00',
            'work_hours' => 8,
            'break_duration' => 60,
        ]);
        $group2->setMemberIds([6, 7, 8]);

        $group3 = new AttendanceGroup();
        $group3->setName('轮班考勤组');
        $group3->setType(AttendanceGroupType::SHIFT);
        $group3->setRules([
            'shift_cycles' => ['morning', 'afternoon', 'night'],
            'rotation_days' => 7,
        ]);
        $group3->setMemberIds([9, 10, 11, 12]);

        $group4 = new AttendanceGroup();
        $group4->setName('空考勤组');
        $group4->setType(AttendanceGroupType::FIXED);
        $group4->setRules([]);
        $group4->setMemberIds([]);

        $groups = [$group1, $group2, $group3, $group4];

        foreach ($groups as $group) {
            $manager->persist($group);
        }
    }

    private function createWorkShifts(ObjectManager $manager): void
    {
        $earlyShift = new WorkShift();
        $earlyShift->setGroupId(1);
        $earlyShift->setName('早班');
        $earlyShift->setStartTime(new \DateTimeImmutable('08:00'));
        $earlyShift->setEndTime(new \DateTimeImmutable('16:00'));
        $earlyShift->setFlexibleMinutes(30);
        $earlyShift->setBreakTimes([['start' => '12:00', 'end' => '13:00']]);
        $earlyShift->setCrossDay(false);

        $midShift = new WorkShift();
        $midShift->setGroupId(1);
        $midShift->setName('中班');
        $midShift->setStartTime(new \DateTimeImmutable('14:00'));
        $midShift->setEndTime(new \DateTimeImmutable('22:00'));
        $midShift->setFlexibleMinutes(30);
        $midShift->setBreakTimes([['start' => '18:00', 'end' => '19:00']]);
        $midShift->setCrossDay(false);

        $nightShift = new WorkShift();
        $nightShift->setGroupId(1);
        $nightShift->setName('夜班');
        $nightShift->setStartTime(new \DateTimeImmutable('22:00'));
        $nightShift->setEndTime(new \DateTimeImmutable('06:00'));
        $nightShift->setFlexibleMinutes(30);
        $nightShift->setBreakTimes([['start' => '02:00', 'end' => '03:00']]);
        $nightShift->setCrossDay(true);

        $shifts = [$earlyShift, $midShift, $nightShift];

        foreach ($shifts as $shift) {
            $manager->persist($shift);
        }
    }

    private function createAttendanceRecords(ObjectManager $manager): void
    {
        // 正常考勤记录
        $record1 = new AttendanceRecord();
        $record1->setEmployeeId(1);
        $record1->setWorkDate(new \DateTimeImmutable('2024-01-15'));
        $record1->setStatus(AttendanceStatus::NORMAL);
        $record1->setCheckInTime(new \DateTimeImmutable('2024-01-15 09:00:00'));
        $record1->setCheckOutTime(new \DateTimeImmutable('2024-01-15 17:00:00'));
        $record1->setCheckInType(CheckInType::CARD);
        $manager->persist($record1);

        // 迟到记录
        $record2 = new AttendanceRecord();
        $record2->setEmployeeId(2);
        $record2->setWorkDate(new \DateTimeImmutable('2024-01-15'));
        $record2->setStatus(AttendanceStatus::LATE);
        $record2->setCheckInTime(new \DateTimeImmutable('2024-01-15 09:30:00'));
        $record2->setCheckInType(CheckInType::APP);
        $manager->persist($record2);

        // 缺勤记录
        $record3 = new AttendanceRecord();
        $record3->setEmployeeId(3);
        $record3->setWorkDate(new \DateTimeImmutable('2024-01-15'));
        $record3->setStatus(AttendanceStatus::ABSENT);
        $manager->persist($record3);
    }

    private function createLeaveApplications(ObjectManager $manager): void
    {
        // 已批准年假申请
        $app1 = new LeaveApplication();
        $app1->setEmployeeId(1);
        $app1->setLeaveType(LeaveType::ANNUAL);
        $app1->setStartDate(new \DateTimeImmutable('2024-02-01'));
        $app1->setEndDate(new \DateTimeImmutable('2024-02-03'));
        $app1->setDuration(3.0);
        $app1->setReason('年假休息');
        $app1->approve(100);
        $manager->persist($app1);

        // 待审批病假申请
        $app2 = new LeaveApplication();
        $app2->setEmployeeId(2);
        $app2->setLeaveType(LeaveType::SICK);
        $app2->setStartDate(new \DateTimeImmutable('2024-01-20'));
        $app2->setEndDate(new \DateTimeImmutable('2024-01-20'));
        $app2->setDuration(1.0);
        $app2->setReason('感冒发烧');
        $manager->persist($app2);
    }

    private function createOvertimeApplications(ObjectManager $manager): void
    {
        // 已批准工作日加班申请
        $app1 = new OvertimeApplication();
        $app1->setEmployeeId(1);
        $app1->setOvertimeDate(new \DateTimeImmutable('2024-01-16'));
        $app1->setStartTime(new \DateTimeImmutable('2024-01-16 18:00:00'));
        $app1->setEndTime(new \DateTimeImmutable('2024-01-16 20:00:00'));
        $app1->setDuration(2.0);
        $app1->setOvertimeType(OvertimeType::WORKDAY);
        $app1->setReason('项目紧急上线');
        $app1->setCompensationType(CompensationType::PAID);
        $app1->approve(100);
        $manager->persist($app1);

        // 待审批周本加班申请
        $app2 = new OvertimeApplication();
        $app2->setEmployeeId(2);
        $app2->setOvertimeDate(new \DateTimeImmutable('2024-01-17'));
        $app2->setStartTime(new \DateTimeImmutable('2024-01-17 09:00:00'));
        $app2->setEndTime(new \DateTimeImmutable('2024-01-17 17:00:00'));
        $app2->setDuration(8.0);
        $app2->setOvertimeType(OvertimeType::WEEKEND);
        $app2->setReason('周末值班');
        $app2->setCompensationType(CompensationType::TIMEOFF);
        $manager->persist($app2);
    }

    private function createHolidayConfigs(ObjectManager $manager): void
    {
        $holiday1 = new HolidayConfig();
        $holiday1->setName('元旦');
        $holiday1->setHolidayDate(new \DateTimeImmutable('2024-01-01'));
        $holiday1->setType(HolidayConfig::TYPE_NATIONAL);
        $holiday1->setDescription('元旦节');
        $holiday1->setPaid(true);
        $holiday1->setMandatory(true);

        $holiday2 = new HolidayConfig();
        $holiday2->setName('春节');
        $holiday2->setHolidayDate(new \DateTimeImmutable('2024-02-10'));
        $holiday2->setType(HolidayConfig::TYPE_NATIONAL);
        $holiday2->setDescription('春节假期');
        $holiday2->setPaid(true);
        $holiday2->setMandatory(true);

        $holiday3 = new HolidayConfig();
        $holiday3->setName('公司年会');
        $holiday3->setHolidayDate(new \DateTimeImmutable('2024-12-31'));
        $holiday3->setType(HolidayConfig::TYPE_COMPANY);
        $holiday3->setDescription('公司年会日');
        $holiday3->setPaid(true);
        $holiday3->setMandatory(false);

        $holidays = [$holiday1, $holiday2, $holiday3];

        foreach ($holidays as $holiday) {
            $manager->persist($holiday);
        }
    }
}
