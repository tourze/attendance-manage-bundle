<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AttendanceManageBundle\Entity\OvertimeApplication;
use Tourze\AttendanceManageBundle\Enum\ApplicationStatus;
use Tourze\AttendanceManageBundle\Enum\CompensationType;
use Tourze\AttendanceManageBundle\Enum\OvertimeType;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(OvertimeApplication::class)]
class OvertimeApplicationTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $entity = new OvertimeApplication();
        $entity->setEmployeeId(1001);
        $entity->setOvertimeDate(new \DateTimeImmutable('2024-01-15'));
        $entity->setStartTime(new \DateTimeImmutable('2024-01-15 18:00:00'));
        $entity->setEndTime(new \DateTimeImmutable('2024-01-15 20:00:00'));
        $entity->setDuration(2.0);
        $entity->setOvertimeType(OvertimeType::WORKDAY);
        $entity->setReason('完成项目紧急需求');

        return $entity;
    }

    public static function propertiesProvider(): iterable
    {
        return [
            'duration' => ['duration', 123.45],
            'overtimeType' => ['overtimeType', OvertimeType::WEEKEND],
            'reason' => ['reason', 'test_value'],
            'compensationType' => ['compensationType', CompensationType::TIMEOFF],
        ];
    }

    public function testConstructor(): void
    {
        $employeeId = 1001;
        $overtimeDate = new \DateTimeImmutable('2024-01-15');
        $startTime = new \DateTimeImmutable('2024-01-15 18:00:00');
        $endTime = new \DateTimeImmutable('2024-01-15 20:00:00');
        $duration = 2.0;
        $overtimeType = OvertimeType::WORKDAY;
        $reason = '完成项目紧急需求';
        $compensationType = CompensationType::PAID;

        $application = new OvertimeApplication();
        $application->setEmployeeId($employeeId);
        $application->setOvertimeDate($overtimeDate);
        $application->setStartTime($startTime);
        $application->setEndTime($endTime);
        $application->setDuration($duration);
        $application->setOvertimeType($overtimeType);
        $application->setReason($reason);
        $application->setCompensationType($compensationType);

        $this->assertEquals($employeeId, $application->getEmployeeId());
        $this->assertEquals($overtimeDate, $application->getOvertimeDate());
        $this->assertEquals($startTime, $application->getStartTime());
        $this->assertEquals($endTime, $application->getEndTime());
        $this->assertEquals($duration, $application->getDuration());
        $this->assertEquals($overtimeType, $application->getOvertimeType());
        $this->assertEquals($reason, $application->getReason());
        $this->assertEquals($compensationType, $application->getCompensationType());
        $this->assertEquals(ApplicationStatus::PENDING, $application->getStatus());
        $this->assertNull($application->getApproverId());
        $this->assertNull($application->getApproveTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $application->getCreateTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $application->getUpdateTime());
    }

    public function testConstructorWithDefaults(): void
    {
        $employeeId = 1001;
        $overtimeDate = new \DateTimeImmutable('2024-01-15');
        $startTime = new \DateTimeImmutable('2024-01-15 18:00:00');
        $endTime = new \DateTimeImmutable('2024-01-15 20:00:00');
        $duration = 2.0;
        $overtimeType = OvertimeType::WORKDAY;

        $application = new OvertimeApplication();
        $application->setEmployeeId($employeeId);
        $application->setOvertimeDate($overtimeDate);
        $application->setStartTime($startTime);
        $application->setEndTime($endTime);
        $application->setDuration($duration);
        $application->setOvertimeType($overtimeType);
        $application->setReason(null);

        $this->assertNull($application->getReason());
        $this->assertEquals(CompensationType::PAID, $application->getCompensationType());
    }

    public function testApprove(): void
    {
        $application = $this->createOvertimeApplication();
        $approverId = 2001;

        $result = $application->approve($approverId);

        $this->assertSame($application, $result);
        $this->assertEquals(ApplicationStatus::APPROVED, $application->getStatus());
        $this->assertEquals($approverId, $application->getApproverId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $application->getApproveTime());
    }

    public function testReject(): void
    {
        $application = $this->createOvertimeApplication();
        $approverId = 2001;

        $result = $application->reject($approverId);

        $this->assertSame($application, $result);
        $this->assertEquals(ApplicationStatus::REJECTED, $application->getStatus());
        $this->assertEquals($approverId, $application->getApproverId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $application->getApproveTime());
    }

    public function testCancel(): void
    {
        $application = $this->createOvertimeApplication();

        $result = $application->cancel();

        $this->assertSame($application, $result);
        $this->assertEquals(ApplicationStatus::CANCELLED, $application->getStatus());
        $this->assertNull($application->getApproverId());
        $this->assertNull($application->getApproveTime());
    }

    public function testStatusCheckers(): void
    {
        $application = $this->createOvertimeApplication();

        $this->assertTrue($application->isPending());
        $this->assertFalse($application->isApproved());
        $this->assertFalse($application->isRejected());
        $this->assertFalse($application->isCancelled());

        $application->approve(2001);
        $this->assertFalse($application->isPending());
        $this->assertTrue($application->isApproved());
        $this->assertFalse($application->isRejected());
        $this->assertFalse($application->isCancelled());

        $application = $this->createOvertimeApplication();
        $application->reject(2001);
        $this->assertFalse($application->isPending());
        $this->assertFalse($application->isApproved());
        $this->assertTrue($application->isRejected());
        $this->assertFalse($application->isCancelled());

        $application = $this->createOvertimeApplication();
        $application->cancel();
        $this->assertFalse($application->isPending());
        $this->assertFalse($application->isApproved());
        $this->assertFalse($application->isRejected());
        $this->assertTrue($application->isCancelled());
    }

    public function testOvertimeTypeCheckers(): void
    {
        $workdayApplication = new OvertimeApplication();
        $workdayApplication->setEmployeeId(1001);
        $workdayApplication->setOvertimeDate(new \DateTimeImmutable('2024-01-15'));
        $workdayApplication->setStartTime(new \DateTimeImmutable('2024-01-15 18:00:00'));
        $workdayApplication->setEndTime(new \DateTimeImmutable('2024-01-15 20:00:00'));
        $workdayApplication->setDuration(2.0);
        $workdayApplication->setOvertimeType(OvertimeType::WORKDAY);

        $this->assertTrue($workdayApplication->isWorkdayOvertime());
        $this->assertFalse($workdayApplication->isWeekendOvertime());
        $this->assertFalse($workdayApplication->isHolidayOvertime());

        $weekendApplication = new OvertimeApplication();
        $weekendApplication->setEmployeeId(1001);
        $weekendApplication->setOvertimeDate(new \DateTimeImmutable('2024-01-13'));
        $weekendApplication->setStartTime(new \DateTimeImmutable('2024-01-13 09:00:00'));
        $weekendApplication->setEndTime(new \DateTimeImmutable('2024-01-13 17:00:00'));
        $weekendApplication->setDuration(8.0);
        $weekendApplication->setOvertimeType(OvertimeType::WEEKEND);

        $this->assertFalse($weekendApplication->isWorkdayOvertime());
        $this->assertTrue($weekendApplication->isWeekendOvertime());
        $this->assertFalse($weekendApplication->isHolidayOvertime());

        $holidayApplication = new OvertimeApplication();
        $holidayApplication->setEmployeeId(1001);
        $holidayApplication->setOvertimeDate(new \DateTimeImmutable('2024-01-01'));
        $holidayApplication->setStartTime(new \DateTimeImmutable('2024-01-01 09:00:00'));
        $holidayApplication->setEndTime(new \DateTimeImmutable('2024-01-01 17:00:00'));
        $holidayApplication->setDuration(8.0);
        $holidayApplication->setOvertimeType(OvertimeType::HOLIDAY);

        $this->assertFalse($holidayApplication->isWorkdayOvertime());
        $this->assertFalse($holidayApplication->isWeekendOvertime());
        $this->assertTrue($holidayApplication->isHolidayOvertime());
    }

    public function testCompensationTypeCheckers(): void
    {
        $paidApplication = new OvertimeApplication();
        $paidApplication->setEmployeeId(1001);
        $paidApplication->setOvertimeDate(new \DateTimeImmutable('2024-01-15'));
        $paidApplication->setStartTime(new \DateTimeImmutable('2024-01-15 18:00:00'));
        $paidApplication->setEndTime(new \DateTimeImmutable('2024-01-15 20:00:00'));
        $paidApplication->setDuration(2.0);
        $paidApplication->setOvertimeType(OvertimeType::WORKDAY);
        $paidApplication->setCompensationType(CompensationType::PAID);

        $this->assertTrue($paidApplication->isPaidCompensation());
        $this->assertFalse($paidApplication->isTimeoffCompensation());

        $timeoffApplication = new OvertimeApplication();
        $timeoffApplication->setEmployeeId(1001);
        $timeoffApplication->setOvertimeDate(new \DateTimeImmutable('2024-01-15'));
        $timeoffApplication->setStartTime(new \DateTimeImmutable('2024-01-15 18:00:00'));
        $timeoffApplication->setEndTime(new \DateTimeImmutable('2024-01-15 20:00:00'));
        $timeoffApplication->setDuration(2.0);
        $timeoffApplication->setOvertimeType(OvertimeType::WORKDAY);
        $timeoffApplication->setCompensationType(CompensationType::TIMEOFF);

        $this->assertFalse($timeoffApplication->isPaidCompensation());
        $this->assertTrue($timeoffApplication->isTimeoffCompensation());
    }

    public function testGetOvertimeMultiplier(): void
    {
        $workdayApplication = new OvertimeApplication();
        $workdayApplication->setEmployeeId(1001);
        $workdayApplication->setOvertimeDate(new \DateTimeImmutable('2024-01-15'));
        $workdayApplication->setStartTime(new \DateTimeImmutable('2024-01-15 18:00:00'));
        $workdayApplication->setEndTime(new \DateTimeImmutable('2024-01-15 20:00:00'));
        $workdayApplication->setDuration(2.0);
        $workdayApplication->setOvertimeType(OvertimeType::WORKDAY);
        $this->assertEquals(1.5, $workdayApplication->getOvertimeMultiplier());

        $weekendApplication = new OvertimeApplication();
        $weekendApplication->setEmployeeId(1001);
        $weekendApplication->setOvertimeDate(new \DateTimeImmutable('2024-01-13'));
        $weekendApplication->setStartTime(new \DateTimeImmutable('2024-01-13 09:00:00'));
        $weekendApplication->setEndTime(new \DateTimeImmutable('2024-01-13 17:00:00'));
        $weekendApplication->setDuration(8.0);
        $weekendApplication->setOvertimeType(OvertimeType::WEEKEND);
        $this->assertEquals(2.0, $weekendApplication->getOvertimeMultiplier());

        $holidayApplication = new OvertimeApplication();
        $holidayApplication->setEmployeeId(1001);
        $holidayApplication->setOvertimeDate(new \DateTimeImmutable('2024-01-01'));
        $holidayApplication->setStartTime(new \DateTimeImmutable('2024-01-01 09:00:00'));
        $holidayApplication->setEndTime(new \DateTimeImmutable('2024-01-01 17:00:00'));
        $holidayApplication->setDuration(8.0);
        $holidayApplication->setOvertimeType(OvertimeType::HOLIDAY);
        $this->assertEquals(3.0, $holidayApplication->getOvertimeMultiplier());
    }

    public function testGetCompensationHours(): void
    {
        $paidWorkdayApplication = new OvertimeApplication();
        $paidWorkdayApplication->setEmployeeId(1001);
        $paidWorkdayApplication->setOvertimeDate(new \DateTimeImmutable('2024-01-15'));
        $paidWorkdayApplication->setStartTime(new \DateTimeImmutable('2024-01-15 18:00:00'));
        $paidWorkdayApplication->setEndTime(new \DateTimeImmutable('2024-01-15 20:00:00'));
        $paidWorkdayApplication->setDuration(2.0);
        $paidWorkdayApplication->setOvertimeType(OvertimeType::WORKDAY);
        $paidWorkdayApplication->setCompensationType(CompensationType::PAID);
        $this->assertEquals(2.0, $paidWorkdayApplication->getCompensationHours());

        $timeoffWorkdayApplication = new OvertimeApplication();
        $timeoffWorkdayApplication->setEmployeeId(1001);
        $timeoffWorkdayApplication->setOvertimeDate(new \DateTimeImmutable('2024-01-15'));
        $timeoffWorkdayApplication->setStartTime(new \DateTimeImmutable('2024-01-15 18:00:00'));
        $timeoffWorkdayApplication->setEndTime(new \DateTimeImmutable('2024-01-15 20:00:00'));
        $timeoffWorkdayApplication->setDuration(2.0);
        $timeoffWorkdayApplication->setOvertimeType(OvertimeType::WORKDAY);
        $timeoffWorkdayApplication->setCompensationType(CompensationType::TIMEOFF);
        $this->assertEquals(3.0, $timeoffWorkdayApplication->getCompensationHours());

        $timeoffWeekendApplication = new OvertimeApplication();
        $timeoffWeekendApplication->setEmployeeId(1001);
        $timeoffWeekendApplication->setOvertimeDate(new \DateTimeImmutable('2024-01-13'));
        $timeoffWeekendApplication->setStartTime(new \DateTimeImmutable('2024-01-13 09:00:00'));
        $timeoffWeekendApplication->setEndTime(new \DateTimeImmutable('2024-01-13 17:00:00'));
        $timeoffWeekendApplication->setDuration(8.0);
        $timeoffWeekendApplication->setOvertimeType(OvertimeType::WEEKEND);
        $timeoffWeekendApplication->setCompensationType(CompensationType::TIMEOFF);
        $this->assertEquals(16.0, $timeoffWeekendApplication->getCompensationHours());
    }

    public function testCanBeModified(): void
    {
        $application = $this->createOvertimeApplication();
        $this->assertTrue($application->canBeModified());

        $application->approve(2001);
        $this->assertFalse($application->canBeModified());

        $application = $this->createOvertimeApplication();
        $application->reject(2001);
        $this->assertFalse($application->canBeModified());

        $application = $this->createOvertimeApplication();
        $application->cancel();
        $this->assertFalse($application->canBeModified());
    }

    public function testCanBeCancelled(): void
    {
        $application = $this->createOvertimeApplication();
        $this->assertTrue($application->canBeCancelled());

        $application->approve(2001);
        $this->assertTrue($application->canBeCancelled());

        $application = $this->createOvertimeApplication();
        $application->reject(2001);
        $this->assertFalse($application->canBeCancelled());

        $application = $this->createOvertimeApplication();
        $application->cancel();
        $this->assertFalse($application->canBeCancelled());
    }

    public function testSetters(): void
    {
        $application = $this->createOvertimeApplication();
        $originalUpdateTime = $application->getUpdateTime();
        usleep(1000);

        $newDate = new \DateTimeImmutable('2024-01-16');
        $newStartTime = new \DateTimeImmutable('2024-01-16 19:00:00');
        $newEndTime = new \DateTimeImmutable('2024-01-16 22:00:00');

        $application->setOvertimeDate($newDate);
        $this->assertEquals($newDate, $application->getOvertimeDate());
        $this->assertGreaterThan($originalUpdateTime, $application->getUpdateTime());

        $application->setStartTime($newStartTime);
        $this->assertEquals($newStartTime, $application->getStartTime());

        $application->setEndTime($newEndTime);
        $this->assertEquals($newEndTime, $application->getEndTime());

        $application->setDuration(3.0);
        $this->assertEquals(3.0, $application->getDuration());

        $application->setOvertimeType(OvertimeType::WEEKEND);
        $this->assertEquals(OvertimeType::WEEKEND, $application->getOvertimeType());

        $application->setReason('新的加班原因');
        $this->assertEquals('新的加班原因', $application->getReason());

        $application->setStatus(ApplicationStatus::APPROVED);
        $this->assertEquals(ApplicationStatus::APPROVED, $application->getStatus());

        $application->setCompensationType(CompensationType::TIMEOFF);
        $this->assertEquals(CompensationType::TIMEOFF, $application->getCompensationType());
    }

    public function testToString(): void
    {
        $application = new OvertimeApplication();
        $application->setEmployeeId(1001);
        $application->setOvertimeDate(new \DateTimeImmutable('2024-01-15'));
        $application->setStartTime(new \DateTimeImmutable('2024-01-15 18:00:00'));
        $application->setEndTime(new \DateTimeImmutable('2024-01-15 20:30:00'));
        $application->setDuration(2.5);
        $application->setOvertimeType(OvertimeType::WORKDAY);

        $expected = '员工1001 2024-01-15加班申请 (18:00-20:30, 2.5小时)';
        $this->assertEquals($expected, (string) $application);
    }

    private function createOvertimeApplication(): OvertimeApplication
    {
        $application = new OvertimeApplication();
        $application->setEmployeeId(1001);
        $application->setOvertimeDate(new \DateTimeImmutable('2024-01-15'));
        $application->setStartTime(new \DateTimeImmutable('2024-01-15 18:00:00'));
        $application->setEndTime(new \DateTimeImmutable('2024-01-15 20:00:00'));
        $application->setDuration(2.0);
        $application->setOvertimeType(OvertimeType::WORKDAY);
        $application->setReason('完成项目紧急需求');

        return $application;
    }
}
