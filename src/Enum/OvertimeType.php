<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum OvertimeType: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case WORKDAY = 'workday';
    case WEEKEND = 'weekend';
    case HOLIDAY = 'holiday';

    public function getLabel(): string
    {
        return match ($this) {
            self::WORKDAY => '工作日加班',
            self::WEEKEND => '周末加班',
            self::HOLIDAY => '节假日加班',
        };
    }

    public function getMultiplier(): float
    {
        return match ($this) {
            self::WORKDAY => 1.5,
            self::WEEKEND => 2.0,
            self::HOLIDAY => 3.0,
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::WORKDAY => BadgeInterface::INFO,
            self::WEEKEND => BadgeInterface::WARNING,
            self::HOLIDAY => BadgeInterface::DANGER,
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
