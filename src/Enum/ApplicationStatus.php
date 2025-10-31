<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum ApplicationStatus: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待审批',
            self::APPROVED => '已批准',
            self::REJECTED => '已拒绝',
            self::CANCELLED => '已取消',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::CANCELLED => 'secondary',
        };
    }

    public function isActive(): bool
    {
        return match ($this) {
            self::PENDING, self::APPROVED => true,
            self::REJECTED, self::CANCELLED => false,
        };
    }

    public function canBeModified(): bool
    {
        return self::PENDING === $this;
    }

    public function canBeCancelled(): bool
    {
        return match ($this) {
            self::PENDING, self::APPROVED => true,
            self::REJECTED, self::CANCELLED => false,
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
