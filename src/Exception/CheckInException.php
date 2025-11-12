<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Exception;

class CheckInException extends AttendanceException
{
    public static function locationTooFarAway(float $distance, float $allowedDistance): self
    {
        return new self(
            sprintf('打卡位置距离过远: %.2fm，允许范围: %.2fm', $distance, $allowedDistance),
            self::CODE_INVALID_LOCATION
        );
    }

    public static function deviceNotAllowed(string $deviceId): self
    {
        return new self(
            sprintf('设备 %s 未被授权打卡', $deviceId),
            self::CODE_INVALID_CHECK_IN
        );
    }

    public static function tooEarlyCheckIn(\DateTimeInterface $earliestTime): self
    {
        return new self(
            sprintf('打卡时间过早，最早可打卡时间: %s', $earliestTime->format('H:i')),
            self::CODE_OUTSIDE_WORK_TIME
        );
    }

    public static function tooLateCheckIn(\DateTimeInterface $latestTime): self
    {
        return new self(
            sprintf('打卡时间过晚，最晚可打卡时间: %s', $latestTime->format('H:i')),
            self::CODE_OUTSIDE_WORK_TIME
        );
    }

    public static function alreadyCheckedIn(): self
    {
        return new self('今日已有打卡记录', self::CODE_DUPLICATE_CHECK_IN);
    }

    public static function alreadyCheckedOut(): self
    {
        return new self('今日已签退', self::CODE_DUPLICATE_CHECK_IN);
    }

    public static function mustCheckInFirst(): self
    {
        return new self('请先打卡上班', self::CODE_INVALID_CHECK_IN);
    }
}
