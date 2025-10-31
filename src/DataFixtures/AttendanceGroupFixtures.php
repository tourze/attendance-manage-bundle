<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\AttendanceManageBundle\Entity\AttendanceGroup;
use Tourze\AttendanceManageBundle\Enum\AttendanceGroupType;

class AttendanceGroupFixtures extends Fixture
{
    public const TECH_GROUP_REFERENCE = 'tech-group';
    public const SALES_GROUP_REFERENCE = 'sales-group';
    public const HR_GROUP_REFERENCE = 'hr-group';
    public const FLEXIBLE_GROUP_REFERENCE = 'flexible-group';
    public const SHIFT_GROUP_REFERENCE = 'shift-group';

    public function load(ObjectManager $manager): void
    {
        // 技术部固定工时考勤组
        $techGroup = new AttendanceGroup();
        $techGroup->setName('技术部考勤组');
        $techGroup->setType(AttendanceGroupType::FIXED);
        $techGroup->setRules([
            'work_start' => '09:00',
            'work_end' => '18:00',
            'break_duration' => 60,
            'flexible_minutes' => 0,
            'location_required' => true,
            'overtime_multiplier' => 1.5,
        ]);
        $techGroup->setMemberIds([101, 102, 103, 104, 105]);
        $manager->persist($techGroup);
        $this->addReference(self::TECH_GROUP_REFERENCE, $techGroup);

        // 销售部弹性工时考勤组
        $salesGroup = new AttendanceGroup();
        $salesGroup->setName('销售部考勤组');
        $salesGroup->setType(AttendanceGroupType::FLEXIBLE);
        $salesGroup->setRules([
            'work_start' => '09:00',
            'work_end' => '18:00',
            'break_duration' => 60,
            'flexible_minutes' => 30,
            'core_work_start' => '10:00',
            'core_work_end' => '16:00',
            'location_required' => false,
            'overtime_multiplier' => 1.5,
        ]);
        $salesGroup->setMemberIds([201, 202, 203, 204]);
        $manager->persist($salesGroup);
        $this->addReference(self::SALES_GROUP_REFERENCE, $salesGroup);

        // 人事部标准考勤组
        $hrGroup = new AttendanceGroup();
        $hrGroup->setName('人事部考勤组');
        $hrGroup->setType(AttendanceGroupType::FIXED);
        $hrGroup->setRules([
            'work_start' => '08:30',
            'work_end' => '17:30',
            'break_duration' => 60,
            'flexible_minutes' => 15,
            'location_required' => true,
            'overtime_multiplier' => 2.0,
        ]);
        $hrGroup->setMemberIds([301, 302, 303]);
        $manager->persist($hrGroup);
        $this->addReference(self::HR_GROUP_REFERENCE, $hrGroup);

        // 客服部高弹性工时组
        $flexibleGroup = new AttendanceGroup();
        $flexibleGroup->setName('客服部弹性考勤组');
        $flexibleGroup->setType(AttendanceGroupType::FLEXIBLE);
        $flexibleGroup->setRules([
            'work_start' => '10:00',
            'work_end' => '19:00',
            'break_duration' => 90,
            'flexible_minutes' => 60,
            'core_work_start' => '11:00',
            'core_work_end' => '17:00',
            'location_required' => false,
            'remote_work_allowed' => true,
            'overtime_multiplier' => 1.5,
        ]);
        $flexibleGroup->setMemberIds([401, 402, 403, 404, 405, 406]);
        $manager->persist($flexibleGroup);
        $this->addReference(self::FLEXIBLE_GROUP_REFERENCE, $flexibleGroup);

        // 生产部轮班制考勤组
        $shiftGroup = new AttendanceGroup();
        $shiftGroup->setName('生产部轮班考勤组');
        $shiftGroup->setType(AttendanceGroupType::SHIFT);
        $shiftGroup->setRules([
            'shift_rotation' => true,
            'night_shift_bonus' => 0.3,
            'weekend_multiplier' => 2.0,
            'holiday_multiplier' => 3.0,
            'location_required' => true,
            'check_in_radius' => 100,
        ]);
        $shiftGroup->setMemberIds([501, 502, 503, 504, 505, 506, 507, 508]);
        $manager->persist($shiftGroup);
        $this->addReference(self::SHIFT_GROUP_REFERENCE, $shiftGroup);

        // 管理层VIP考勤组
        $managementGroup = new AttendanceGroup();
        $managementGroup->setName('管理层考勤组');
        $managementGroup->setType(AttendanceGroupType::FLEXIBLE);
        $managementGroup->setRules([
            'work_start' => '09:30',
            'work_end' => '17:30',
            'break_duration' => 90,
            'flexible_minutes' => 120,
            'location_required' => false,
            'remote_work_allowed' => true,
            'no_overtime_tracking' => true,
            'executive_privileges' => true,
        ]);
        $managementGroup->setMemberIds([1, 2, 3]); // 高级管理员工ID
        $manager->persist($managementGroup);

        // 实习生考勤组
        $internGroup = new AttendanceGroup();
        $internGroup->setName('实习生考勤组');
        $internGroup->setType(AttendanceGroupType::FIXED);
        $internGroup->setRules([
            'work_start' => '09:00',
            'work_end' => '17:00',
            'break_duration' => 60,
            'flexible_minutes' => 10,
            'location_required' => true,
            'overtime_not_allowed' => true,
            'max_daily_hours' => 8,
            'supervisor_approval_required' => true,
        ]);
        $internGroup->setMemberIds([801, 802, 803, 804, 805]);
        $manager->persist($internGroup);

        // 外包团队考勤组
        $contractorGroup = new AttendanceGroup();
        $contractorGroup->setName('外包团队考勤组');
        $contractorGroup->setType(AttendanceGroupType::FLEXIBLE);
        $contractorGroup->setRules([
            'work_start' => '10:00',
            'work_end' => '18:00',
            'flexible_minutes' => 45,
            'location_required' => false,
            'project_based_tracking' => true,
            'hourly_rate_tracking' => true,
            'overtime_multiplier' => 1.2,
        ]);
        $contractorGroup->setMemberIds([901, 902, 903, 904]);
        $manager->persist($contractorGroup);

        $manager->flush();
    }

    /**
     * @return class-string[]
     */
    public function getDependencies(): array
    {
        return [];
    }
}
