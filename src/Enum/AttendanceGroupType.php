<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum AttendanceGroupType: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case FIXED = 'fixed';
    case FLEXIBLE = 'flexible';
    case SHIFT = 'shift';

    public function getLabel(): string
    {
        return match ($this) {
            self::FIXED => '固定时间',
            self::FLEXIBLE => '弹性时间',
            self::SHIFT => '轮班制',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::FIXED => 'primary',
            self::FLEXIBLE => 'success',
            self::SHIFT => 'warning',
        };
    }

    public function isFixedTime(): bool
    {
        return self::FIXED === $this;
    }

    public function isFlexibleTime(): bool
    {
        return self::FLEXIBLE === $this;
    }

    public function isShiftWork(): bool
    {
        return self::SHIFT === $this;
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
