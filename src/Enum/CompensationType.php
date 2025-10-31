<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum CompensationType: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case PAID = 'paid';
    case TIMEOFF = 'timeoff';

    public function getLabel(): string
    {
        return match ($this) {
            self::PAID => '加班费',
            self::TIMEOFF => '调休',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::PAID => '以现金形式支付加班费',
            self::TIMEOFF => '以调休时间补偿加班',
        };
    }

    /**
     * 获取用于EasyAdmin选择字段的选项数组
     *
     * @return array<string, string>
     */
    public static function getSelectChoices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->getLabel()] = $case->value;
        }

        return $choices;
    }
}
