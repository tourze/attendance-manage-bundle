<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AttendanceManageBundle\Enum\LeaveType;
use Tourze\AttendanceManageBundle\Exception\AttendanceException;
use Tourze\AttendanceManageBundle\Exception\LeaveApplicationException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(LeaveApplicationException::class)]
class LeaveApplicationExceptionTest extends AbstractExceptionTestCase
{
    public function testInvalidDateRangeShouldCreateExceptionWithCorrectMessage(): void
    {
        $exception = LeaveApplicationException::invalidDateRange();

        $this->assertInstanceOf(LeaveApplicationException::class, $exception);
        $this->assertSame('请假结束时间必须晚于开始时间', $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_LEAVE_CONFLICT, $exception->getCode());
    }

    public function testDateInPastShouldCreateExceptionWithCorrectMessage(): void
    {
        $exception = LeaveApplicationException::dateInPast();

        $this->assertInstanceOf(LeaveApplicationException::class, $exception);
        $this->assertSame('不能申请过去日期的请假', $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_LEAVE_CONFLICT, $exception->getCode());
    }

    public function testOverlappingLeaveShouldCreateExceptionWithDateRange(): void
    {
        $startDate = new \DateTimeImmutable('2023-12-01');
        $endDate = new \DateTimeImmutable('2023-12-05');
        $exception = LeaveApplicationException::overlappingLeave($startDate, $endDate);

        $expectedMessage = sprintf(
            '与已有请假时间冲突: %s - %s',
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        $this->assertInstanceOf(LeaveApplicationException::class, $exception);
        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_LEAVE_CONFLICT, $exception->getCode());
    }

    public function testInsufficientBalanceShouldCreateExceptionWithBalanceInfo(): void
    {
        $leaveType = LeaveType::ANNUAL;

        $requested = 16.0;
        $available = 8.0;
        $exception = LeaveApplicationException::insufficientBalance($leaveType, $requested, $available);

        $expectedMessage = sprintf(
            '%s余额不足，申请: %.1f小时，可用: %.1f小时',
            $leaveType->getLabel(),
            $requested,
            $available
        );

        $this->assertInstanceOf(LeaveApplicationException::class, $exception);
        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_INVALID_LEAVE_BALANCE, $exception->getCode());
    }

    public function testCannotModifyApprovedLeaveShouldCreateExceptionWithCorrectMessage(): void
    {
        $exception = LeaveApplicationException::cannotModifyApprovedLeave();

        $this->assertInstanceOf(LeaveApplicationException::class, $exception);
        $this->assertSame('已批准的请假不能修改', $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_LEAVE_CONFLICT, $exception->getCode());
    }

    public function testCannotCancelProcessedLeaveShouldCreateExceptionWithCorrectMessage(): void
    {
        $exception = LeaveApplicationException::cannotCancelProcessedLeave();

        $this->assertInstanceOf(LeaveApplicationException::class, $exception);
        $this->assertSame('已处理的请假不能取消', $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_LEAVE_CONFLICT, $exception->getCode());
    }

    public function testExceedsMaximumDurationShouldCreateExceptionWithMaxDays(): void
    {
        $maxDays = 15;
        $exception = LeaveApplicationException::exceedsMaximumDuration($maxDays);

        $expectedMessage = sprintf('请假天数超过最大限制: %d天', $maxDays);

        $this->assertInstanceOf(LeaveApplicationException::class, $exception);
        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_LEAVE_CONFLICT, $exception->getCode());
    }

    public function testRequiresAdvanceNoticeShouldCreateExceptionWithRequiredDays(): void
    {
        $requiredDays = 3;
        $exception = LeaveApplicationException::requiresAdvanceNotice($requiredDays);

        $expectedMessage = sprintf('需要提前 %d 天申请', $requiredDays);

        $this->assertInstanceOf(LeaveApplicationException::class, $exception);
        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_LEAVE_CONFLICT, $exception->getCode());
    }

    public function testLeaveApplicationExceptionShouldExtendAttendanceException(): void
    {
        $exception = LeaveApplicationException::invalidDateRange();

        $this->assertInstanceOf(AttendanceException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testOverlappingLeaveWithDifferentDateTimeImplementationsShouldWork(): void
    {
        $startDate = new \DateTime('2023-12-10 00:00:00');
        $endDate = new \DateTimeImmutable('2023-12-15 23:59:59');
        $exception = LeaveApplicationException::overlappingLeave($startDate, $endDate);

        $expectedMessage = '与已有请假时间冲突: 2023-12-10 - 2023-12-15';

        $this->assertSame($expectedMessage, $exception->getMessage());
    }

    public function testInsufficientBalanceWithZeroValuesShouldFormatCorrectly(): void
    {
        $leaveType = LeaveType::SICK;

        $requested = 0.0;
        $available = 0.0;
        $exception = LeaveApplicationException::insufficientBalance($leaveType, $requested, $available);

        $expectedMessage = $leaveType->getLabel() . '余额不足，申请: 0.0小时，可用: 0.0小时';

        $this->assertSame($expectedMessage, $exception->getMessage());
    }

    public function testInsufficientBalanceWithDecimalValuesShouldFormatCorrectly(): void
    {
        $leaveType = LeaveType::PERSONAL;

        $requested = 4.5;
        $available = 2.5;
        $exception = LeaveApplicationException::insufficientBalance($leaveType, $requested, $available);

        $expectedMessage = $leaveType->getLabel() . '余额不足，申请: 4.5小时，可用: 2.5小时';

        $this->assertSame($expectedMessage, $exception->getMessage());
    }
}
