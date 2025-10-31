<?php

declare(strict_types=1);

namespace TourzeAttendanceManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AttendanceManageBundle\Entity\LeaveApplication;
use Tourze\AttendanceManageBundle\Enum\ApplicationStatus;
use Tourze\AttendanceManageBundle\Enum\LeaveType;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(LeaveApplication::class)]
class LeaveApplicationTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $entity = new LeaveApplication();
        $entity->setEmployeeId(123);
        $entity->setLeaveType($this->leaveType);
        $entity->setStartDate(new \DateTimeImmutable('2023-12-01'));
        $entity->setEndDate(new \DateTimeImmutable('2023-12-05'));
        $entity->setDuration(5.0);
        $entity->setReason('年假休息');

        return $entity;
    }

    public static function propertiesProvider(): iterable
    {
        return [
            'duration' => ['duration', 123.45],
            'reason' => ['reason', 'test_value'],
        ];
    }

    private LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->leaveType = LeaveType::ANNUAL;
    }

    public function testConstructorShouldSetPropertiesCorrectly(): void
    {
        $employeeId = 123;
        $startDate = new \DateTimeImmutable('2023-12-01');
        $endDate = new \DateTimeImmutable('2023-12-05');
        $duration = 5.0;
        $reason = '年假休息';

        $application = new LeaveApplication();
        $application->setEmployeeId($employeeId);
        $application->setLeaveType($this->leaveType);
        $application->setStartDate($startDate);
        $application->setEndDate($endDate);
        $application->setDuration($duration);
        $application->setReason($reason);

        $this->assertEquals($employeeId, $application->getEmployeeId());
        $this->assertSame($this->leaveType, $application->getLeaveType());
        $this->assertSame($startDate, $application->getStartDate());
        $this->assertSame($endDate, $application->getEndDate());
        $this->assertEquals($duration, $application->getDuration());
        $this->assertEquals($reason, $application->getReason());
        $this->assertEquals(ApplicationStatus::PENDING, $application->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $application->getCreateTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $application->getUpdateTime());
    }

    public function testConstructorWithoutReasonShouldSetReasonToNull(): void
    {
        $employeeId = 123;
        $startDate = new \DateTimeImmutable('2023-12-01');
        $endDate = new \DateTimeImmutable('2023-12-05');
        $duration = 5.0;

        $application = new LeaveApplication();
        $application->setEmployeeId($employeeId);
        $application->setLeaveType($this->leaveType);
        $application->setStartDate($startDate);
        $application->setEndDate($endDate);
        $application->setDuration($duration);
        $application->setReason(null);

        $this->assertNull($application->getReason());
    }

    public function testSetLeaveTypeShouldUpdateTypeAndUpdatedAt(): void
    {
        $application = $this->createLeaveApplication();
        $originalUpdatedAt = $application->getUpdateTime();

        sleep(1);

        $newLeaveType = LeaveType::SICK;
        $application->setLeaveType($newLeaveType);

        $this->assertSame($newLeaveType, $application->getLeaveType());
        $this->assertGreaterThan($originalUpdatedAt, $application->getUpdateTime());
    }

    public function testSetStartDateShouldUpdateDateAndUpdatedAt(): void
    {
        $application = $this->createLeaveApplication();
        $originalUpdatedAt = $application->getUpdateTime();

        sleep(1);

        $newStartDate = new \DateTimeImmutable('2023-12-10');
        $application->setStartDate($newStartDate);

        $this->assertSame($newStartDate, $application->getStartDate());
        $this->assertGreaterThan($originalUpdatedAt, $application->getUpdateTime());
    }

    public function testSetEndDateShouldUpdateDateAndUpdatedAt(): void
    {
        $application = $this->createLeaveApplication();
        $originalUpdatedAt = $application->getUpdateTime();

        sleep(1);

        $newEndDate = new \DateTimeImmutable('2023-12-15');
        $application->setEndDate($newEndDate);

        $this->assertSame($newEndDate, $application->getEndDate());
        $this->assertGreaterThan($originalUpdatedAt, $application->getUpdateTime());
    }

    public function testSetDurationShouldUpdateDurationAndUpdatedAt(): void
    {
        $application = $this->createLeaveApplication();
        $originalUpdatedAt = $application->getUpdateTime();

        sleep(1);

        $newDuration = 10.0;
        $application->setDuration($newDuration);

        $this->assertEquals($newDuration, $application->getDuration());
        $this->assertGreaterThan($originalUpdatedAt, $application->getUpdateTime());
    }

    public function testSetReasonShouldUpdateReasonAndUpdatedAt(): void
    {
        $application = $this->createLeaveApplication();
        $originalUpdatedAt = $application->getUpdateTime();

        sleep(1);

        $newReason = '家庭事务';
        $application->setReason($newReason);

        $this->assertEquals($newReason, $application->getReason());
        $this->assertGreaterThan($originalUpdatedAt, $application->getUpdateTime());
    }

    public function testSetStatusShouldUpdateStatusAndUpdatedAt(): void
    {
        $application = $this->createLeaveApplication();
        $originalUpdatedAt = $application->getUpdateTime();

        sleep(1);

        $application->setStatus(ApplicationStatus::APPROVED);

        $this->assertEquals(ApplicationStatus::APPROVED, $application->getStatus());
        $this->assertGreaterThan($originalUpdatedAt, $application->getUpdateTime());
    }

    public function testApproveShouldSetStatusAndApprovalInfo(): void
    {
        $application = $this->createLeaveApplication();
        $approverId = 456;
        $originalUpdatedAt = $application->getUpdateTime();

        sleep(1);

        $result = $application->approve($approverId);

        $this->assertSame($application, $result);
        $this->assertEquals(ApplicationStatus::APPROVED, $application->getStatus());
        $this->assertEquals($approverId, $application->getApproverId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $application->getApproveTime());
        $this->assertGreaterThan($originalUpdatedAt, $application->getUpdateTime());
    }

    public function testRejectShouldSetStatusAndApprovalInfo(): void
    {
        $application = $this->createLeaveApplication();
        $approverId = 456;
        $originalUpdatedAt = $application->getUpdateTime();

        sleep(1);

        $result = $application->reject($approverId);

        $this->assertSame($application, $result);
        $this->assertEquals(ApplicationStatus::REJECTED, $application->getStatus());
        $this->assertEquals($approverId, $application->getApproverId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $application->getApproveTime());
        $this->assertGreaterThan($originalUpdatedAt, $application->getUpdateTime());
    }

    public function testCancelShouldSetStatusToCancelled(): void
    {
        $application = $this->createLeaveApplication();
        $originalUpdatedAt = $application->getUpdateTime();

        sleep(1);

        $result = $application->cancel();

        $this->assertSame($application, $result);
        $this->assertEquals(ApplicationStatus::CANCELLED, $application->getStatus());
        $this->assertGreaterThan($originalUpdatedAt, $application->getUpdateTime());
    }

    public function testIsPendingShouldReturnTrueForPendingStatus(): void
    {
        $application = $this->createLeaveApplication();

        $this->assertTrue($application->isPending());
        $this->assertFalse($application->isApproved());
        $this->assertFalse($application->isRejected());
        $this->assertFalse($application->isCancelled());
    }

    public function testIsApprovedShouldReturnTrueForApprovedStatus(): void
    {
        $application = $this->createLeaveApplication();
        $application->approve(456);

        $this->assertTrue($application->isApproved());
        $this->assertFalse($application->isPending());
        $this->assertFalse($application->isRejected());
        $this->assertFalse($application->isCancelled());
    }

    public function testIsRejectedShouldReturnTrueForRejectedStatus(): void
    {
        $application = $this->createLeaveApplication();
        $application->reject(456);

        $this->assertTrue($application->isRejected());
        $this->assertFalse($application->isPending());
        $this->assertFalse($application->isApproved());
        $this->assertFalse($application->isCancelled());
    }

    public function testIsCancelledShouldReturnTrueForCancelledStatus(): void
    {
        $application = $this->createLeaveApplication();
        $application->cancel();

        $this->assertTrue($application->isCancelled());
        $this->assertFalse($application->isPending());
        $this->assertFalse($application->isApproved());
        $this->assertFalse($application->isRejected());
    }

    public function testIsProcessedShouldReturnFalseForPendingStatus(): void
    {
        $application = $this->createLeaveApplication();

        $this->assertFalse($application->isProcessed());
    }

    public function testIsProcessedShouldReturnTrueForNonPendingStatus(): void
    {
        $application = $this->createLeaveApplication();
        $application->approve(456);

        $this->assertTrue($application->isProcessed());
    }

    public function testCanBeModifiedShouldReturnTrueForPendingStatus(): void
    {
        $application = $this->createLeaveApplication();

        $this->assertTrue($application->canBeModified());
    }

    public function testCanBeModifiedShouldReturnFalseForProcessedStatus(): void
    {
        $application = $this->createLeaveApplication();
        $application->approve(456);

        $this->assertFalse($application->canBeModified());
    }

    public function testCanBeCancelledShouldReturnTrueForPendingStatus(): void
    {
        $application = $this->createLeaveApplication();

        $this->assertTrue($application->canBeCancelled());
    }

    public function testCanBeCancelledShouldReturnTrueForApprovedStatus(): void
    {
        $application = $this->createLeaveApplication();
        $application->approve(456);

        $this->assertTrue($application->canBeCancelled());
    }

    public function testCanBeCancelledShouldReturnFalseForRejectedStatus(): void
    {
        $application = $this->createLeaveApplication();
        $application->reject(456);

        $this->assertFalse($application->canBeCancelled());
    }

    public function testGetDurationInDaysShouldCalculateCorrectDays(): void
    {
        $startDate = new \DateTimeImmutable('2023-12-01');
        $endDate = new \DateTimeImmutable('2023-12-05');
        $application = new LeaveApplication();
        $application->setEmployeeId(123);
        $application->setLeaveType($this->leaveType);
        $application->setStartDate($startDate);
        $application->setEndDate($endDate);
        $application->setDuration(5.0);

        $this->assertEquals(5, $application->getDurationInDays());
    }

    public function testGetDurationInDaysForSingleDayShouldReturnOne(): void
    {
        $startDate = new \DateTimeImmutable('2023-12-01');
        $endDate = new \DateTimeImmutable('2023-12-01');
        $application = new LeaveApplication();
        $application->setEmployeeId(123);
        $application->setLeaveType($this->leaveType);
        $application->setStartDate($startDate);
        $application->setEndDate($endDate);
        $application->setDuration(1.0);

        $this->assertEquals(1, $application->getDurationInDays());
    }

    public function testIsOverlappingShouldReturnTrueForOverlappingDates(): void
    {
        $application = $this->createLeaveApplication();
        $checkStart = new \DateTimeImmutable('2023-12-03');
        $checkEnd = new \DateTimeImmutable('2023-12-07');

        $this->assertTrue($application->isOverlapping($checkStart, $checkEnd));
    }

    public function testIsOverlappingShouldReturnFalseForNonOverlappingDates(): void
    {
        $application = $this->createLeaveApplication();
        $checkStart = new \DateTimeImmutable('2023-12-10');
        $checkEnd = new \DateTimeImmutable('2023-12-15');

        $this->assertFalse($application->isOverlapping($checkStart, $checkEnd));
    }

    public function testGetDateRangeShouldReturnAllDatesInRange(): void
    {
        $startDate = new \DateTimeImmutable('2023-12-01');
        $endDate = new \DateTimeImmutable('2023-12-03');
        $application = new LeaveApplication();
        $application->setEmployeeId(123);
        $application->setLeaveType($this->leaveType);
        $application->setStartDate($startDate);
        $application->setEndDate($endDate);
        $application->setDuration(3.0);

        $dateRange = $application->getDateRange();

        $this->assertCount(3, $dateRange);
        $this->assertEquals('2023-12-01', $dateRange[0]->format('Y-m-d'));
        $this->assertEquals('2023-12-02', $dateRange[1]->format('Y-m-d'));
        $this->assertEquals('2023-12-03', $dateRange[2]->format('Y-m-d'));
    }

    public function testToStringShouldReturnFormattedString(): void
    {
        $application = $this->createLeaveApplication();

        $expected = '员工123 ' . $this->leaveType->value . '请假申请 (2023-12-01至2023-12-05, 5.0天)';
        $this->assertEquals($expected, $application->__toString());
        $this->assertEquals($expected, (string) $application);
    }

    public function testStatusConstantsShouldHaveCorrectValues(): void
    {
        $this->assertEquals('pending', ApplicationStatus::PENDING->value);
        $this->assertEquals('approved', ApplicationStatus::APPROVED->value);
        $this->assertEquals('rejected', ApplicationStatus::REJECTED->value);
        $this->assertEquals('cancelled', ApplicationStatus::CANCELLED->value);
    }

    public function testImplementsStringableInterface(): void
    {
        $application = $this->createLeaveApplication();

        $this->assertInstanceOf(\Stringable::class, $application);
    }

    public function testInitialStateShouldBeCorrect(): void
    {
        $application = $this->createLeaveApplication();

        $this->assertNull($application->getApproverId());
        $this->assertNull($application->getApproveTime());
        $this->assertTrue($application->isPending());
        $this->assertTrue($application->canBeModified());
        $this->assertTrue($application->canBeCancelled());
        $this->assertFalse($application->isProcessed());
    }

    private function createLeaveApplication(): LeaveApplication
    {
        $employeeId = 123;
        $startDate = new \DateTimeImmutable('2023-12-01');
        $endDate = new \DateTimeImmutable('2023-12-05');
        $duration = 5.0;
        $reason = '年假休息';

        $application = new LeaveApplication();
        $application->setEmployeeId($employeeId);
        $application->setLeaveType($this->leaveType);
        $application->setStartDate($startDate);
        $application->setEndDate($endDate);
        $application->setDuration($duration);
        $application->setReason($reason);

        return $application;
    }
}
