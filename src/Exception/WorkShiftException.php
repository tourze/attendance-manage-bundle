<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Exception;

/** @phpstan-ignore-next-line forbiddenExtendOfNonAbstractClass */
class WorkShiftException extends AttendanceException
{
    public static function invalidTimeFormat(string $timeString): self
    {
        return new self("无效的时间格式: {$timeString}");
    }

    public static function timeCreationFailed(string $timeString): self
    {
        return new self("时间创建失败: {$timeString}");
    }
}
