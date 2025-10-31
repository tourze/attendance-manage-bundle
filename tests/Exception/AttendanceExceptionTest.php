<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AttendanceManageBundle\Exception\AttendanceException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(AttendanceException::class)]
class AttendanceExceptionTest extends AbstractExceptionTestCase
{
    public function testInvalidCheckInShouldCreateExceptionWithCorrectMessageAndCode(): void
    {
        $reason = '设备未授权';
        $exception = AttendanceException::invalidCheckIn($reason);

        $this->assertInstanceOf(AttendanceException::class, $exception);
        $this->assertSame($reason, $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_INVALID_CHECK_IN, $exception->getCode());
    }

    public function testDuplicateCheckInShouldCreateExceptionWithCorrectMessageAndCode(): void
    {
        $exception = AttendanceException::duplicateCheckIn();

        $this->assertInstanceOf(AttendanceException::class, $exception);
        $this->assertSame('重复打卡', $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_DUPLICATE_CHECK_IN, $exception->getCode());
    }

    public function testInvalidLocationShouldCreateExceptionWithCorrectMessageAndCode(): void
    {
        $exception = AttendanceException::invalidLocation();

        $this->assertInstanceOf(AttendanceException::class, $exception);
        $this->assertSame('打卡位置无效', $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_INVALID_LOCATION, $exception->getCode());
    }

    public function testOutsideWorkTimeShouldCreateExceptionWithCorrectMessageAndCode(): void
    {
        $exception = AttendanceException::outsideWorkTime();

        $this->assertInstanceOf(AttendanceException::class, $exception);
        $this->assertSame('不在工作时间范围内', $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_OUTSIDE_WORK_TIME, $exception->getCode());
    }

    public function testNoAttendanceGroupShouldCreateExceptionWithEmployeeIdInMessage(): void
    {
        $employeeId = 123;
        $exception = AttendanceException::noAttendanceGroup($employeeId);

        $this->assertInstanceOf(AttendanceException::class, $exception);
        $this->assertSame(sprintf('员工 %d 未分配考勤组', $employeeId), $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_NO_ATTENDANCE_GROUP, $exception->getCode());
    }

    public function testInsufficientLeaveBalanceShouldCreateExceptionWithLeaveTypeInMessage(): void
    {
        $leaveType = '年假';
        $exception = AttendanceException::insufficientLeaveBalance($leaveType);

        $this->assertInstanceOf(AttendanceException::class, $exception);
        $this->assertSame(sprintf('%s余额不足', $leaveType), $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_INVALID_LEAVE_BALANCE, $exception->getCode());
    }

    public function testLeaveConflictShouldCreateExceptionWithCorrectMessageAndCode(): void
    {
        $exception = AttendanceException::leaveConflict();

        $this->assertInstanceOf(AttendanceException::class, $exception);
        $this->assertSame('请假时间冲突', $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_LEAVE_CONFLICT, $exception->getCode());
    }

    public function testExceedPatchLimitShouldCreateExceptionWithCorrectMessageAndCode(): void
    {
        $exception = AttendanceException::exceedPatchLimit();

        $this->assertInstanceOf(AttendanceException::class, $exception);
        $this->assertSame('超过补卡次数限制', $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_EXCEED_PATCH_LIMIT, $exception->getCode());
    }

    public function testInvalidOvertimeShouldCreateExceptionWithReasonAndCode(): void
    {
        $reason = '超出最大加班时长';
        $exception = AttendanceException::invalidOvertime($reason);

        $this->assertInstanceOf(AttendanceException::class, $exception);
        $this->assertSame($reason, $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_INVALID_OVERTIME, $exception->getCode());
    }

    public function testRecordNotFoundShouldCreateExceptionWithRecordIdInMessage(): void
    {
        $recordId = 456;
        $exception = AttendanceException::recordNotFound($recordId);

        $this->assertInstanceOf(AttendanceException::class, $exception);
        $this->assertSame(sprintf('考勤记录 %d 不存在', $recordId), $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_RECORD_NOT_FOUND, $exception->getCode());
    }

    public function testExceptionConstantsShouldHaveCorrectValues(): void
    {
        $this->assertSame(1001, AttendanceException::CODE_INVALID_CHECK_IN);
        $this->assertSame(1002, AttendanceException::CODE_DUPLICATE_CHECK_IN);
        $this->assertSame(1003, AttendanceException::CODE_INVALID_LOCATION);
        $this->assertSame(1004, AttendanceException::CODE_OUTSIDE_WORK_TIME);
        $this->assertSame(1005, AttendanceException::CODE_NO_ATTENDANCE_GROUP);
        $this->assertSame(1006, AttendanceException::CODE_INVALID_LEAVE_BALANCE);
        $this->assertSame(1007, AttendanceException::CODE_LEAVE_CONFLICT);
        $this->assertSame(1008, AttendanceException::CODE_EXCEED_PATCH_LIMIT);
        $this->assertSame(1009, AttendanceException::CODE_INVALID_OVERTIME);
        $this->assertSame(1010, AttendanceException::CODE_RECORD_NOT_FOUND);
    }

    public function testExceptionShouldExtendRuntimeException(): void
    {
        $exception = AttendanceException::duplicateCheckIn();

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }
}
