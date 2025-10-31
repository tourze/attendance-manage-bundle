<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum LeaveType: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case ANNUAL = 'annual';
    case SICK = 'sick';
    case PERSONAL = 'personal';
    case MARRIAGE = 'marriage';
    case MATERNITY = 'maternity';
    case PATERNITY = 'paternity';
    case BEREAVEMENT = 'bereavement';
    case COMPENSATORY = 'compensatory';
    case UNPAID = 'unpaid';

    public function getLabel(): string
    {
        return match ($this) {
            self::ANNUAL => '年假',
            self::SICK => '病假',
            self::PERSONAL => '事假',
            self::MARRIAGE => '婚假',
            self::MATERNITY => '产假',
            self::PATERNITY => '陪产假',
            self::BEREAVEMENT => '丧假',
            self::COMPENSATORY => '调休',
            self::UNPAID => '无薪假',
        };
    }

    public function isPaid(): bool
    {
        return match ($this) {
            self::ANNUAL, self::SICK, self::MARRIAGE,
            self::MATERNITY, self::PATERNITY,
            self::BEREAVEMENT, self::COMPENSATORY => true,
            self::PERSONAL, self::UNPAID => false,
        };
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::ANNUAL => 'info',           // 年假 - 蓝色，正常福利假
            self::SICK => 'warning',          // 病假 - 橙色，健康问题需要注意
            self::PERSONAL => 'secondary',    // 事假 - 灰色，个人事务
            self::MARRIAGE => 'success',      // 婚假 - 绿色，喜庆事情
            self::MATERNITY => 'primary',     // 产假 - 主色调，重要假期
            self::PATERNITY => 'primary',     // 陪产假 - 主色调，重要假期
            self::BEREAVEMENT => 'dark',      // 丧假 - 深色，严肃事情
            self::COMPENSATORY => 'light',    // 调休 - 浅色，工作时间调整
            self::UNPAID => 'danger',         // 无薪假 - 红色，无薪状态
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
