<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\AttendanceManageBundle\Entity\LeaveApplication;
use Tourze\AttendanceManageBundle\Enum\ApplicationStatus;
use Tourze\AttendanceManageBundle\Enum\LeaveType;

class LeaveApplicationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 创建一些示例请假申请
        $applications = [
            [
                'employeeId' => 1001,
                'leaveType' => LeaveType::ANNUAL,
                'startDate' => new \DateTimeImmutable('2024-03-15'),
                'endDate' => new \DateTimeImmutable('2024-03-17'),
                'duration' => 3.0,
                'reason' => '年假休息',
                'status' => ApplicationStatus::APPROVED,
            ],
            [
                'employeeId' => 1002,
                'leaveType' => LeaveType::SICK,
                'startDate' => new \DateTimeImmutable('2024-03-20'),
                'endDate' => new \DateTimeImmutable('2024-03-20'),
                'duration' => 1.0,
                'reason' => '感冒发烧',
                'status' => ApplicationStatus::PENDING,
            ],
            [
                'employeeId' => 1003,
                'leaveType' => LeaveType::PERSONAL,
                'startDate' => new \DateTimeImmutable('2024-03-25'),
                'endDate' => new \DateTimeImmutable('2024-03-26'),
                'duration' => 2.0,
                'reason' => '家庭事务',
                'status' => ApplicationStatus::REJECTED,
            ],
        ];

        foreach ($applications as $data) {
            $application = new LeaveApplication();
            $application->setEmployeeId($data['employeeId']);
            $application->setLeaveType($data['leaveType']);
            $application->setStartDate($data['startDate']);
            $application->setEndDate($data['endDate']);
            $application->setDuration($data['duration']);
            $application->setReason($data['reason']);

            if (ApplicationStatus::APPROVED === $data['status']) {
                $application->approve(2001);
            } elseif (ApplicationStatus::REJECTED === $data['status']) {
                $application->reject(2001);
            }

            $manager->persist($application);
        }

        $manager->flush();
    }
}
