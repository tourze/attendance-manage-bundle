<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum WorkShiftType: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case FIXED = 'fixed';
    case FLEXIBLE = 'flexible';
    case SHIFT = 'shift';

    public function getLabel(): string
    {
        return match ($this) {
            self::FIXED => '固定工时',
            self::FLEXIBLE => '弹性工时',
            self::SHIFT => '排班制',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::FIXED => '每天固定上下班时间',
            self::FLEXIBLE => '在允许范围内灵活调整上下班时间',
            self::SHIFT => '按排班表工作',
        };
    }
}
