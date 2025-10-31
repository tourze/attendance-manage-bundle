<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Exception;

use Tourze\AttendanceManageBundle\Enum\LeaveType;

/** @phpstan-ignore-next-line forbiddenExtendOfNonAbstractClass */
class LeaveApplicationException extends AttendanceException
{
    public static function invalidDateRange(): self
    {
        return new self('请假结束时间必须晚于开始时间', self::CODE_LEAVE_CONFLICT);
    }

    public static function dateInPast(): self
    {
        return new self('不能申请过去日期的请假', self::CODE_LEAVE_CONFLICT);
    }

    public static function overlappingLeave(\DateTimeInterface $startDate, \DateTimeInterface $endDate): self
    {
        return new self(
            sprintf(
                '与已有请假时间冲突: %s - %s',
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ),
            self::CODE_LEAVE_CONFLICT
        );
    }

    public static function insufficientBalance(LeaveType $leaveType, float $requested, float $available): self
    {
        return new self(
            sprintf(
                '%s余额不足，申请: %.1f小时，可用: %.1f小时',
                $leaveType->getLabel(),
                $requested,
                $available
            ),
            self::CODE_INVALID_LEAVE_BALANCE
        );
    }

    public static function cannotModifyApprovedLeave(): self
    {
        return new self('已批准的请假不能修改', self::CODE_LEAVE_CONFLICT);
    }

    public static function cannotCancelProcessedLeave(): self
    {
        return new self('已处理的请假不能取消', self::CODE_LEAVE_CONFLICT);
    }

    public static function exceedsMaximumDuration(int $maxDays): self
    {
        return new self(
            sprintf('请假天数超过最大限制: %d天', $maxDays),
            self::CODE_LEAVE_CONFLICT
        );
    }

    public static function requiresAdvanceNotice(int $requiredDays): self
    {
        return new self(
            sprintf('需要提前 %d 天申请', $requiredDays),
            self::CODE_LEAVE_CONFLICT
        );
    }
}
