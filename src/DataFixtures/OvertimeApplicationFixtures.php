<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Tourze\AttendanceManageBundle\Entity\OvertimeApplication;
use Tourze\AttendanceManageBundle\Enum\ApplicationStatus;
use Tourze\AttendanceManageBundle\Enum\CompensationType;
use Tourze\AttendanceManageBundle\Enum\OvertimeType;

class OvertimeApplicationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('zh_CN');

        // 使用固定的员工ID列表
        $employeeIds = [1, 2, 3, 4, 5];

        $reasons = [
            '项目上线前的功能开发',
            '紧急生产问题修复',
            '客户紧急需求开发',
            '系统升级维护',
            '季度报表制作',
            '年度总结准备',
            '重要会议准备',
            '团队培训材料准备',
        ];

        $statuses = [
            ApplicationStatus::PENDING,
            ApplicationStatus::APPROVED,
            ApplicationStatus::REJECTED,
            ApplicationStatus::CANCELLED,
        ];

        $overtimeTypes = [
            OvertimeType::WORKDAY,
            OvertimeType::WEEKEND,
            OvertimeType::HOLIDAY,
        ];

        $compensationTypes = [
            CompensationType::PAID,
            CompensationType::TIMEOFF,
        ];

        for ($i = 0; $i < 20; ++$i) {
            $overtimeDate = \DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-2 months', 'now'));
            $startDateTime = $faker->dateTimeBetween('-2 months', 'now');
            $minute = $faker->randomElement([0, 30]);
            assert(is_int($minute));
            $startDateTime->setTime($faker->numberBetween(18, 20), $minute, 0);
            $startTime = \DateTimeImmutable::createFromMutable($startDateTime);

            $endDateTime = clone $startDateTime;
            $endDateTime->modify('+' . $faker->numberBetween(1, 4) . ' hours');
            $endTime = \DateTimeImmutable::createFromMutable($endDateTime);

            $duration = round(($endDateTime->getTimestamp() - $startDateTime->getTimestamp()) / 3600, 2);

            $employeeId = $faker->randomElement($employeeIds);
            assert(is_int($employeeId));
            $overtimeType = $faker->randomElement($overtimeTypes);
            assert($overtimeType instanceof OvertimeType);
            $reason = $faker->randomElement($reasons);
            assert(is_string($reason));
            $compensationType = $faker->randomElement($compensationTypes);
            assert($compensationType instanceof CompensationType);
            $status = $faker->randomElement($statuses);
            assert($status instanceof ApplicationStatus);

            $application = new OvertimeApplication();
            $application->setEmployeeId($employeeId);
            $application->setOvertimeDate($overtimeDate);
            $application->setStartTime($startTime);
            $application->setEndTime($endTime);
            $application->setDuration($duration);
            $application->setOvertimeType($overtimeType);
            $application->setReason($reason);
            $application->setCompensationType($compensationType);

            // 设置状态
            $application->setStatus($status);

            // 如果是已审批或已拒绝，设置审批人
            if (ApplicationStatus::APPROVED === $status) {
                $approverId = $faker->randomElement($employeeIds);
                assert(is_int($approverId));
                $application->approve($approverId);
            } elseif (ApplicationStatus::REJECTED === $status) {
                $rejectorId = $faker->randomElement($employeeIds);
                assert(is_int($rejectorId));
                $application->reject($rejectorId);
            }

            $manager->persist($application);
            $this->addReference('overtime_application_' . ($i + 1), $application);
        }

        $manager->flush();
    }
}
