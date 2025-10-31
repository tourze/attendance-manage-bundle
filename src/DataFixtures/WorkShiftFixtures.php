<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\AttendanceManageBundle\Entity\WorkShift;
use Tourze\AttendanceManageBundle\Exception\WorkShiftException;

class WorkShiftFixtures extends Fixture implements DependentFixtureInterface
{
    public const DAY_SHIFT_REFERENCE = 'day-shift';
    public const NIGHT_SHIFT_REFERENCE = 'night-shift';
    public const FLEXIBLE_SHIFT_REFERENCE = 'flexible-shift';

    public function load(ObjectManager $manager): void
    {
        $this->createStandardShifts($manager);
        $this->createFlexibleShifts($manager);
        $this->createProductionShifts($manager);
        $this->createSpecialShifts($manager);

        $manager->flush();
    }

    private function createStandardShifts(ObjectManager $manager): void
    {
        // 标准白班 (9:00-18:00)
        $startTime = \DateTimeImmutable::createFromFormat('H:i', '09:00');
        $endTime = \DateTimeImmutable::createFromFormat('H:i', '18:00');
        if (false === $startTime || false === $endTime) {
            throw WorkShiftException::timeCreationFailed('09:00 或 18:00');
        }

        $dayShift = new WorkShift();
        $dayShift->setGroupId(1); // 技术部考勤组
        $dayShift->setName('标准白班');
        $dayShift->setStartTime($startTime);
        $dayShift->setEndTime($endTime);
        $dayShift->setFlexibleMinutes(0); // 无弹性时间
        $dayShift->setBreakTimes([]);
        $dayShift->setCrossDay(false);
        $manager->persist($dayShift);
        $this->addReference(self::DAY_SHIFT_REFERENCE, $dayShift);

        // 早班 (8:00-17:00)
        $earlyStartTime = \DateTimeImmutable::createFromFormat('H:i', '08:00');
        $earlyEndTime = \DateTimeImmutable::createFromFormat('H:i', '17:00');
        if (false === $earlyStartTime || false === $earlyEndTime) {
            throw WorkShiftException::timeCreationFailed('08:00 或 17:00');
        }

        $earlyShift = new WorkShift();
        $earlyShift->setGroupId(1); // 技术部考勤组
        $earlyShift->setName('早班');
        $earlyShift->setStartTime($earlyStartTime);
        $earlyShift->setEndTime($earlyEndTime);
        $earlyShift->setFlexibleMinutes(15); // 15分钟弹性时间
        $earlyShift->setBreakTimes([]);
        $earlyShift->setCrossDay(false);
        $manager->persist($earlyShift);

        // 人事部标准班次 (8:30-17:30)
        $hrStartTime = \DateTimeImmutable::createFromFormat('H:i', '08:30');
        $hrEndTime = \DateTimeImmutable::createFromFormat('H:i', '17:30');
        if (false === $hrStartTime || false === $hrEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $hrShift = new WorkShift();
        $hrShift->setGroupId(3); // 人事部考勤组ID
        $hrShift->setName('人事部标准班次');
        $hrShift->setStartTime($hrStartTime);
        $hrShift->setEndTime($hrEndTime);
        $hrShift->setFlexibleMinutes(15); // 15分钟弹性时间
        $hrShift->setBreakTimes([]);
        $hrShift->setCrossDay(false);
        $manager->persist($hrShift);

        // 管理层弹性班次 (9:30-17:30)
        $mgmtStartTime = \DateTimeImmutable::createFromFormat('H:i', '09:30');
        $mgmtEndTime = \DateTimeImmutable::createFromFormat('H:i', '17:30');
        if (false === $mgmtStartTime || false === $mgmtEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $managementShift = new WorkShift();
        $managementShift->setGroupId(6); // 管理层考勤组ID
        $managementShift->setName('管理层弹性班次');
        $managementShift->setStartTime($mgmtStartTime);
        $managementShift->setEndTime($mgmtEndTime);
        $managementShift->setFlexibleMinutes(120); // 2小时弹性时间
        $managementShift->setBreakTimes([]);
        $managementShift->setCrossDay(false);
        $manager->persist($managementShift);
    }

    private function createFlexibleShifts(ObjectManager $manager): void
    {
        // 销售部弹性班次 (9:00-18:00, 30分钟弹性)
        $salesStartTime = \DateTimeImmutable::createFromFormat('H:i', '09:00');
        $salesEndTime = \DateTimeImmutable::createFromFormat('H:i', '18:00');
        if (false === $salesStartTime || false === $salesEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $salesFlexible = new WorkShift();
        $salesFlexible->setGroupId(2); // 销售部考勤组ID
        $salesFlexible->setName('销售部弹性班次');
        $salesFlexible->setStartTime($salesStartTime);
        $salesFlexible->setEndTime($salesEndTime);
        $salesFlexible->setFlexibleMinutes(30);
        $salesFlexible->setBreakTimes([]);
        $salesFlexible->setCrossDay(false);
        $manager->persist($salesFlexible);
        $this->addReference(self::FLEXIBLE_SHIFT_REFERENCE, $salesFlexible);

        // 客服部高弹性班次 (10:00-19:00, 60分钟弹性)
        $csStartTime = \DateTimeImmutable::createFromFormat('H:i', '10:00');
        $csEndTime = \DateTimeImmutable::createFromFormat('H:i', '19:00');
        if (false === $csStartTime || false === $csEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $customerServiceFlexible = new WorkShift();
        $customerServiceFlexible->setGroupId(4); // 客服部考勤组ID
        $customerServiceFlexible->setName('客服部高弹性班次');
        $customerServiceFlexible->setStartTime($csStartTime);
        $customerServiceFlexible->setEndTime($csEndTime);
        $customerServiceFlexible->setFlexibleMinutes(60);
        $customerServiceFlexible->setBreakTimes([]);
        $customerServiceFlexible->setCrossDay(false);
        $manager->persist($customerServiceFlexible);

        // 研发部核心时间班次 (10:00-16:00核心 + 弹性)
        $rdStartTime = \DateTimeImmutable::createFromFormat('H:i', '08:00');
        $rdEndTime = \DateTimeImmutable::createFromFormat('H:i', '20:00');
        if (false === $rdStartTime || false === $rdEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $rdCoreTime = new WorkShift();
        $rdCoreTime->setGroupId(1); // 技术部考勤组ID
        $rdCoreTime->setName('研发部核心时间');
        $rdCoreTime->setStartTime($rdStartTime); // 最早可到达时间
        $rdCoreTime->setEndTime($rdEndTime); // 最晚可离开时间
        $rdCoreTime->setFlexibleMinutes(240); // 4小时弹性时间，核心工作时间10:00-16:00
        $rdCoreTime->setBreakTimes([]);
        $rdCoreTime->setCrossDay(false);
        $manager->persist($rdCoreTime);

        // 外包团队灵活班次
        $contractorStartTime = \DateTimeImmutable::createFromFormat('H:i', '10:00');
        $contractorEndTime = \DateTimeImmutable::createFromFormat('H:i', '18:00');
        if (false === $contractorStartTime || false === $contractorEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $contractorShift = new WorkShift();
        $contractorShift->setGroupId(8); // 外包团队考勤组ID
        $contractorShift->setName('外包团队灵活班次');
        $contractorShift->setStartTime($contractorStartTime);
        $contractorShift->setEndTime($contractorEndTime);
        $contractorShift->setFlexibleMinutes(45);
        $contractorShift->setBreakTimes([]);
        $contractorShift->setCrossDay(false);
        $manager->persist($contractorShift);
    }

    private function createProductionShifts(ObjectManager $manager): void
    {
        // 生产白班 (8:00-16:00)
        $prodDayStartTime = \DateTimeImmutable::createFromFormat('H:i', '08:00');
        $prodDayEndTime = \DateTimeImmutable::createFromFormat('H:i', '16:00');
        if (false === $prodDayStartTime || false === $prodDayEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $prodDayShift = new WorkShift();
        $prodDayShift->setGroupId(5); // 生产部轮班考勤组ID
        $prodDayShift->setName('生产白班');
        $prodDayShift->setStartTime($prodDayStartTime);
        $prodDayShift->setEndTime($prodDayEndTime);
        $prodDayShift->setFlexibleMinutes(0); // 生产班次无弹性时间
        $prodDayShift->setBreakTimes([]);
        $prodDayShift->setCrossDay(false);
        $manager->persist($prodDayShift);

        // 生产中班 (16:00-00:00)
        $prodAfternoonStartTime = \DateTimeImmutable::createFromFormat('H:i', '16:00');
        $prodAfternoonEndTime = \DateTimeImmutable::createFromFormat('H:i', '00:00');
        if (false === $prodAfternoonStartTime || false === $prodAfternoonEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $prodAfternoonShift = new WorkShift();
        $prodAfternoonShift->setGroupId(5); // 生产部轮班考勤组ID
        $prodAfternoonShift->setName('生产中班');
        $prodAfternoonShift->setStartTime($prodAfternoonStartTime);
        $prodAfternoonShift->setEndTime($prodAfternoonEndTime);
        $prodAfternoonShift->setFlexibleMinutes(0);
        $prodAfternoonShift->setBreakTimes([]);
        $prodAfternoonShift->setCrossDay(true); // 跨天班次
        $manager->persist($prodAfternoonShift);

        // 生产夜班 (00:00-08:00) - 跨天班次
        $prodNightStartTime = \DateTimeImmutable::createFromFormat('H:i', '00:00');
        $prodNightEndTime = \DateTimeImmutable::createFromFormat('H:i', '08:00');
        if (false === $prodNightStartTime || false === $prodNightEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $prodNightShift = new WorkShift();
        $prodNightShift->setGroupId(5); // 生产部轮班考勤组ID
        $prodNightShift->setName('生产夜班');
        $prodNightShift->setStartTime($prodNightStartTime);
        $prodNightShift->setEndTime($prodNightEndTime);
        $prodNightShift->setFlexibleMinutes(0);
        $prodNightShift->setBreakTimes([]);
        $prodNightShift->setCrossDay(true); // 跨天班次
        $manager->persist($prodNightShift);
        $this->addReference(self::NIGHT_SHIFT_REFERENCE, $prodNightShift);

        // 保安夜班 (22:00-06:00) - 跨天班次
        $securityStartTime = \DateTimeImmutable::createFromFormat('H:i', '22:00');
        $securityEndTime = \DateTimeImmutable::createFromFormat('H:i', '06:00');
        if (false === $securityStartTime || false === $securityEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $securityNightShift = new WorkShift();
        $securityNightShift->setGroupId(5); // 生产部轮班考勤组ID
        $securityNightShift->setName('保安夜班');
        $securityNightShift->setStartTime($securityStartTime);
        $securityNightShift->setEndTime($securityEndTime);
        $securityNightShift->setFlexibleMinutes(15); // 15分钟弹性时间
        $securityNightShift->setBreakTimes([]);
        $securityNightShift->setCrossDay(true); // 跨天班次
        $manager->persist($securityNightShift);

        // 清洁夜班 (23:00-05:00) - 跨天班次
        $cleaningStartTime = \DateTimeImmutable::createFromFormat('H:i', '23:00');
        $cleaningEndTime = \DateTimeImmutable::createFromFormat('H:i', '05:00');
        if (false === $cleaningStartTime || false === $cleaningEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $cleaningNightShift = new WorkShift();
        $cleaningNightShift->setGroupId(5); // 生产部轮班考勤组ID
        $cleaningNightShift->setName('清洁夜班');
        $cleaningNightShift->setStartTime($cleaningStartTime);
        $cleaningNightShift->setEndTime($cleaningEndTime);
        $cleaningNightShift->setFlexibleMinutes(30); // 30分钟弹性时间
        $cleaningNightShift->setBreakTimes([]);
        $cleaningNightShift->setCrossDay(true); // 跨天班次
        $manager->persist($cleaningNightShift);
    }

    private function createSpecialShifts(ObjectManager $manager): void
    {
        // 周末值班班次 (10:00-15:00)
        $weekendStartTime = \DateTimeImmutable::createFromFormat('H:i', '10:00');
        $weekendEndTime = \DateTimeImmutable::createFromFormat('H:i', '15:00');
        if (false === $weekendStartTime || false === $weekendEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $weekendShift = new WorkShift();
        $weekendShift->setGroupId(1); // 技术部考勤组ID
        $weekendShift->setName('周末值班班次');
        $weekendShift->setStartTime($weekendStartTime);
        $weekendShift->setEndTime($weekendEndTime);
        $weekendShift->setFlexibleMinutes(30);
        $weekendShift->setBreakTimes([]);
        $weekendShift->setCrossDay(false);
        $manager->persist($weekendShift);

        // 节假日应急班次 (9:00-12:00)
        $emergencyStartTime = \DateTimeImmutable::createFromFormat('H:i', '09:00');
        $emergencyEndTime = \DateTimeImmutable::createFromFormat('H:i', '12:00');
        if (false === $emergencyStartTime || false === $emergencyEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $holidayEmergencyShift = new WorkShift();
        $holidayEmergencyShift->setGroupId(1); // 技术部考勤组ID
        $holidayEmergencyShift->setName('节假日应急班次');
        $holidayEmergencyShift->setStartTime($emergencyStartTime);
        $holidayEmergencyShift->setEndTime($emergencyEndTime);
        $holidayEmergencyShift->setFlexibleMinutes(0);
        $holidayEmergencyShift->setBreakTimes([]);
        $holidayEmergencyShift->setCrossDay(false);
        $manager->persist($holidayEmergencyShift);

        // 实习生专用班次 (9:00-17:00)
        $internStartTime = \DateTimeImmutable::createFromFormat('H:i', '09:00');
        $internEndTime = \DateTimeImmutable::createFromFormat('H:i', '17:00');
        if (false === $internStartTime || false === $internEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $internShift = new WorkShift();
        $internShift->setGroupId(7); // 实习生考勤组ID
        $internShift->setName('实习生专用班次');
        $internShift->setStartTime($internStartTime);
        $internShift->setEndTime($internEndTime);
        $internShift->setFlexibleMinutes(10); // 10分钟弹性时间
        $internShift->setBreakTimes([]);
        $internShift->setCrossDay(false);
        $manager->persist($internShift);

        // 临时项目班次 (14:00-22:00)
        $projectStartTime = \DateTimeImmutable::createFromFormat('H:i', '14:00');
        $projectEndTime = \DateTimeImmutable::createFromFormat('H:i', '22:00');
        if (false === $projectStartTime || false === $projectEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $projectShift = new WorkShift();
        $projectShift->setGroupId(1); // 技术部考勤组ID
        $projectShift->setName('临时项目班次');
        $projectShift->setStartTime($projectStartTime);
        $projectShift->setEndTime($projectEndTime);
        $projectShift->setFlexibleMinutes(30);
        $projectShift->setBreakTimes([]);
        $projectShift->setCrossDay(false);
        $manager->persist($projectShift);

        // 客户支持班次 (12:00-21:00)
        $supportStartTime = \DateTimeImmutable::createFromFormat('H:i', '12:00');
        $supportEndTime = \DateTimeImmutable::createFromFormat('H:i', '21:00');
        if (false === $supportStartTime || false === $supportEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $supportShift = new WorkShift();
        $supportShift->setGroupId(4); // 客服部考勤组ID
        $supportShift->setName('客户支持班次');
        $supportShift->setStartTime($supportStartTime);
        $supportShift->setEndTime($supportEndTime);
        $supportShift->setFlexibleMinutes(45);
        $supportShift->setBreakTimes([]);
        $supportShift->setCrossDay(false);
        $manager->persist($supportShift);

        // 海外团队班次 (21:00-05:00) - 跨天
        $overseasStartTime = \DateTimeImmutable::createFromFormat('H:i', '21:00');
        $overseasEndTime = \DateTimeImmutable::createFromFormat('H:i', '05:00');
        if (false === $overseasStartTime || false === $overseasEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $overseasShift = new WorkShift();
        $overseasShift->setGroupId(1); // 技术部考勤组ID
        $overseasShift->setName('海外团队班次');
        $overseasShift->setStartTime($overseasStartTime);
        $overseasShift->setEndTime($overseasEndTime);
        $overseasShift->setFlexibleMinutes(60); // 1小时弹性时间
        $overseasShift->setBreakTimes([]);
        $overseasShift->setCrossDay(true); // 跨天班次
        $manager->persist($overseasShift);

        // 测试专用短班次 (10:00-14:00)
        $testStartTime = \DateTimeImmutable::createFromFormat('H:i', '10:00');
        $testEndTime = \DateTimeImmutable::createFromFormat('H:i', '14:00');
        if (false === $testStartTime || false === $testEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $testShift = new WorkShift();
        $testShift->setGroupId(1); // 技术部考勤组ID
        $testShift->setName('测试专用短班次');
        $testShift->setStartTime($testStartTime);
        $testShift->setEndTime($testEndTime);
        $testShift->setFlexibleMinutes(15);
        $testShift->setBreakTimes([]);
        $testShift->setCrossDay(false);
        $manager->persist($testShift);
    }

    /**
     * @return array<class-string<FixtureInterface>>
     */
    public function getDependencies(): array
    {
        return [
            AttendanceGroupFixtures::class,
        ];
    }
}
