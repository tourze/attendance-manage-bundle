<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum CheckInType: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case CARD = 'card';
    case FINGERPRINT = 'fingerprint';
    case FACE = 'face';
    case APP = 'app';
    case WIFI = 'wifi';
    case BLUETOOTH = 'bluetooth';
    case QR_CODE = 'qr_code';
    case MANUAL = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::CARD => '刷卡',
            self::FINGERPRINT => '指纹',
            self::FACE => '人脸识别',
            self::APP => 'APP打卡',
            self::WIFI => 'WiFi打卡',
            self::BLUETOOTH => '蓝牙打卡',
            self::QR_CODE => '二维码',
            self::MANUAL => '手动补卡',
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::CARD => 'primary',
            self::FINGERPRINT => 'success',
            self::FACE => 'info',
            self::APP => 'warning',
            self::WIFI => 'secondary',
            self::BLUETOOTH => 'dark',
            self::QR_CODE => 'light',
            self::MANUAL => 'danger',
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
