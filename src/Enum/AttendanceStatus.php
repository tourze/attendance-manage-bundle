<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum AttendanceStatus: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case NORMAL = 'normal';
    case LATE = 'late';
    case EARLY = 'early';
    case ABSENT = 'absent';
    case LEAVE = 'leave';
    case OVERTIME = 'overtime';
    case HOLIDAY = 'holiday';

    public function getLabel(): string
    {
        return match ($this) {
            self::NORMAL => '正常',
            self::LATE => '迟到',
            self::EARLY => '早退',
            self::ABSENT => '旷工',
            self::LEAVE => '请假',
            self::OVERTIME => '加班',
            self::HOLIDAY => '假期',
        };
    }

    public static function fromValue(string $value): self
    {
        return self::from($value);
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];
        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->getLabel();
        }

        return $labels;
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::NORMAL => 'success',
            self::LATE => 'warning',
            self::EARLY => 'warning',
            self::ABSENT => 'danger',
            self::LEAVE => 'info',
            self::OVERTIME => 'primary',
            self::HOLIDAY => 'secondary',
        };
    }

    public function getBadgeType(): string
    {
        return 'badge';
    }

    public function getBadge(): string
    {
        return sprintf(
            '<span class="badge badge-%s">%s</span>',
            $this->getBadgeColor(),
            $this->getLabel()
        );
    }
}
