<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Service;

use Tourze\AttendanceManageBundle\Entity\WorkShift;

class ShiftMatcher
{
    /**
     * @param array<WorkShift> $shifts
     */
    public function getCurrentShift(array $shifts, \DateTimeInterface $checkTime): ?WorkShift
    {
        $checkTimeFormatted = $checkTime->format('H:i');

        foreach ($shifts as $shift) {
            if (!$shift->isActive()) {
                continue;
            }

            if ($this->isTimeInShift($checkTimeFormatted, $shift)) {
                return $shift;
            }
        }

        return null;
    }

    private function isTimeInShift(string $checkTime, WorkShift $shift): bool
    {
        $startTime = $shift->getStartTime()->format('H:i');
        $endTime = $shift->getEndTime()->format('H:i');

        return $shift->isCrossDay()
            ? $this->isTimeInCrossDayShift($checkTime, $startTime, $endTime)
            : $this->isTimeInRegularShift($checkTime, $startTime, $endTime);
    }

    private function isTimeInCrossDayShift(string $checkTime, string $startTime, string $endTime): bool
    {
        return $checkTime >= $startTime || $checkTime <= $endTime;
    }

    private function isTimeInRegularShift(string $checkTime, string $startTime, string $endTime): bool
    {
        return $checkTime >= $startTime && $checkTime <= $endTime;
    }
}
