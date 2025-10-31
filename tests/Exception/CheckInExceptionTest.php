<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AttendanceManageBundle\Exception\AttendanceException;
use Tourze\AttendanceManageBundle\Exception\CheckInException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(CheckInException::class)]
class CheckInExceptionTest extends AbstractExceptionTestCase
{
    public function testLocationTooFarAwayShouldCreateExceptionWithDistanceInfo(): void
    {
        $distance = 150.75;
        $allowedDistance = 100.0;
        $exception = CheckInException::locationTooFarAway($distance, $allowedDistance);

        $expectedMessage = sprintf('打卡位置距离过远: %.2fm，允许范围: %.2fm', $distance, $allowedDistance);

        $this->assertInstanceOf(CheckInException::class, $exception);
        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_INVALID_LOCATION, $exception->getCode());
    }

    public function testDeviceNotAllowedShouldCreateExceptionWithDeviceId(): void
    {
        $deviceId = 'DEVICE-12345';
        $exception = CheckInException::deviceNotAllowed($deviceId);

        $expectedMessage = sprintf('设备 %s 未被授权打卡', $deviceId);

        $this->assertInstanceOf(CheckInException::class, $exception);
        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_INVALID_CHECK_IN, $exception->getCode());
    }

    public function testTooEarlyCheckInShouldCreateExceptionWithEarliestTime(): void
    {
        $earliestTime = new \DateTimeImmutable('2023-12-01 09:00:00');
        $exception = CheckInException::tooEarlyCheckIn($earliestTime);

        $expectedMessage = sprintf('打卡时间过早，最早可打卡时间: %s', $earliestTime->format('H:i'));

        $this->assertInstanceOf(CheckInException::class, $exception);
        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_OUTSIDE_WORK_TIME, $exception->getCode());
    }

    public function testTooLateCheckInShouldCreateExceptionWithLatestTime(): void
    {
        $latestTime = new \DateTimeImmutable('2023-12-01 18:00:00');
        $exception = CheckInException::tooLateCheckIn($latestTime);

        $expectedMessage = sprintf('打卡时间过晚，最晚可打卡时间: %s', $latestTime->format('H:i'));

        $this->assertInstanceOf(CheckInException::class, $exception);
        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_OUTSIDE_WORK_TIME, $exception->getCode());
    }

    public function testAlreadyCheckedInShouldCreateExceptionWithCorrectMessage(): void
    {
        $exception = CheckInException::alreadyCheckedIn();

        $this->assertInstanceOf(CheckInException::class, $exception);
        $this->assertSame('今日已有打卡记录', $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_DUPLICATE_CHECK_IN, $exception->getCode());
    }

    public function testAlreadyCheckedOutShouldCreateExceptionWithCorrectMessage(): void
    {
        $exception = CheckInException::alreadyCheckedOut();

        $this->assertInstanceOf(CheckInException::class, $exception);
        $this->assertSame('今日已签退', $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_DUPLICATE_CHECK_IN, $exception->getCode());
    }

    public function testMustCheckInFirstShouldCreateExceptionWithCorrectMessage(): void
    {
        $exception = CheckInException::mustCheckInFirst();

        $this->assertInstanceOf(CheckInException::class, $exception);
        $this->assertSame('请先打卡上班', $exception->getMessage());
        $this->assertSame(AttendanceException::CODE_INVALID_CHECK_IN, $exception->getCode());
    }

    public function testCheckInExceptionShouldExtendAttendanceException(): void
    {
        $exception = CheckInException::alreadyCheckedIn();

        $this->assertInstanceOf(AttendanceException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testLocationTooFarAwayWithZeroDistancesShouldFormatCorrectly(): void
    {
        $distance = 0.0;
        $allowedDistance = 0.0;
        $exception = CheckInException::locationTooFarAway($distance, $allowedDistance);

        $expectedMessage = '打卡位置距离过远: 0.00m，允许范围: 0.00m';

        $this->assertSame($expectedMessage, $exception->getMessage());
    }

    public function testTooEarlyCheckInWithDateTimeShouldFormatTimeCorrectly(): void
    {
        $earliestTime = new \DateTime('2023-12-01 08:30:15');
        $exception = CheckInException::tooEarlyCheckIn($earliestTime);

        $expectedMessage = '打卡时间过早，最早可打卡时间: 08:30';

        $this->assertSame($expectedMessage, $exception->getMessage());
    }

    public function testTooLateCheckInWithDateTimeShouldFormatTimeCorrectly(): void
    {
        $latestTime = new \DateTime('2023-12-01 17:45:30');
        $exception = CheckInException::tooLateCheckIn($latestTime);

        $expectedMessage = '打卡时间过晚，最晚可打卡时间: 17:45';

        $this->assertSame($expectedMessage, $exception->getMessage());
    }
}
