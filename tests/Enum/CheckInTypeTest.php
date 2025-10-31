<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AttendanceManageBundle\Enum\CheckInType;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(CheckInType::class)]
class CheckInTypeTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('card', CheckInType::CARD->value);
        $this->assertSame('fingerprint', CheckInType::FINGERPRINT->value);
        $this->assertSame('face', CheckInType::FACE->value);
        $this->assertSame('app', CheckInType::APP->value);
        $this->assertSame('wifi', CheckInType::WIFI->value);
        $this->assertSame('bluetooth', CheckInType::BLUETOOTH->value);
        $this->assertSame('qr_code', CheckInType::QR_CODE->value);
        $this->assertSame('manual', CheckInType::MANUAL->value);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('刷卡', CheckInType::CARD->getLabel());
        $this->assertSame('指纹', CheckInType::FINGERPRINT->getLabel());
        $this->assertSame('人脸识别', CheckInType::FACE->getLabel());
        $this->assertSame('APP打卡', CheckInType::APP->getLabel());
        $this->assertSame('WiFi打卡', CheckInType::WIFI->getLabel());
        $this->assertSame('蓝牙打卡', CheckInType::BLUETOOTH->getLabel());
        $this->assertSame('二维码', CheckInType::QR_CODE->getLabel());
        $this->assertSame('手动补卡', CheckInType::MANUAL->getLabel());
    }

    public function testFromValue(): void
    {
        $this->assertSame(CheckInType::CARD, CheckInType::from('card'));
        $this->assertSame(CheckInType::FINGERPRINT, CheckInType::from('fingerprint'));
        $this->assertSame(CheckInType::FACE, CheckInType::from('face'));
        $this->assertSame(CheckInType::APP, CheckInType::from('app'));
        $this->assertSame(CheckInType::WIFI, CheckInType::from('wifi'));
        $this->assertSame(CheckInType::BLUETOOTH, CheckInType::from('bluetooth'));
        $this->assertSame(CheckInType::QR_CODE, CheckInType::from('qr_code'));
        $this->assertSame(CheckInType::MANUAL, CheckInType::from('manual'));
    }

    public function testTryFromInvalidValue(): void
    {
        $this->assertNull(CheckInType::tryFrom('invalid'));
    }

    public function testToArray(): void
    {
        $array = CheckInType::CARD->toArray();
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('card', $array['value']);
        $this->assertEquals('刷卡', $array['label']);
    }
}
