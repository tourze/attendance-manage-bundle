<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Exception;

class AttendanceException extends \RuntimeException
{
    public const CODE_INVALID_CHECK_IN = 1001;
    public const CODE_DUPLICATE_CHECK_IN = 1002;
    public const CODE_INVALID_LOCATION = 1003;
    public const CODE_OUTSIDE_WORK_TIME = 1004;
    public const CODE_NO_ATTENDANCE_GROUP = 1005;
    public const CODE_INVALID_LEAVE_BALANCE = 1006;
    public const CODE_LEAVE_CONFLICT = 1007;
    public const CODE_EXCEED_PATCH_LIMIT = 1008;
    public const CODE_INVALID_OVERTIME = 1009;
    public const CODE_RECORD_NOT_FOUND = 1010;

    public static function invalidCheckIn(string $reason): self
    {
        return new self($reason, self::CODE_INVALID_CHECK_IN);
    }

    public static function duplicateCheckIn(): self
    {
        return new self('重复打卡', self::CODE_DUPLICATE_CHECK_IN);
    }

    public static function invalidLocation(): self
    {
        return new self('打卡位置无效', self::CODE_INVALID_LOCATION);
    }

    public static function outsideWorkTime(): self
    {
        return new self('不在工作时间范围内', self::CODE_OUTSIDE_WORK_TIME);
    }

    public static function noAttendanceGroup(int $employeeId): self
    {
        return new self(sprintf('员工 %d 未分配考勤组', $employeeId), self::CODE_NO_ATTENDANCE_GROUP);
    }

    public static function insufficientLeaveBalance(string $leaveType): self
    {
        return new self(sprintf('%s余额不足', $leaveType), self::CODE_INVALID_LEAVE_BALANCE);
    }

    public static function leaveConflict(): self
    {
        return new self('请假时间冲突', self::CODE_LEAVE_CONFLICT);
    }

    public static function exceedPatchLimit(): self
    {
        return new self('超过补卡次数限制', self::CODE_EXCEED_PATCH_LIMIT);
    }

    public static function invalidOvertime(string $reason): self
    {
        return new self($reason, self::CODE_INVALID_OVERTIME);
    }

    public static function recordNotFound(int $recordId): self
    {
        return new self(sprintf('考勤记录 %d 不存在', $recordId), self::CODE_RECORD_NOT_FOUND);
    }

    public static function attendanceGroupNotFound(int $groupId): self
    {
        return new self('考勤组不存在', self::CODE_RECORD_NOT_FOUND);
    }
}
