<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Service;

use Tourze\AttendanceManageBundle\Entity\AttendanceGroup;
use Tourze\AttendanceManageBundle\Entity\AttendanceRecord;
use Tourze\AttendanceManageBundle\Entity\WorkShift;
use Tourze\AttendanceManageBundle\Enum\AttendanceStatus;

readonly class AttendanceStatusCalculator
{
    public function __construct(
        private RuleService $ruleService,
    ) {
    }

    public function calculateCheckInStatus(int $employeeId, \DateTimeInterface $checkTime): AttendanceStatus
    {
        try {
            $rules = $this->ruleService->getApplicableRules($employeeId, $checkTime);
            $shifts = $rules['shifts'] ?? [];

            if (!is_array($shifts) || 0 === count($shifts)) {
                return AttendanceStatus::NORMAL;
            }

            foreach ($shifts as $shift) {
                if (!$shift instanceof WorkShift || !$shift->isActive()) {
                    continue;
                }

                $group = $rules['group'] ?? null;
                $status = $this->calculateCheckInStatusForShift($shift, $checkTime, $group instanceof AttendanceGroup ? $group : null);
                if (AttendanceStatus::NORMAL !== $status) {
                    return $status;
                }
            }
        } catch (\Exception) {
            // Fallback to normal status on any error
        }

        return AttendanceStatus::NORMAL;
    }

    public function calculateCheckOutStatus(AttendanceRecord $record, \DateTimeInterface $checkTime): AttendanceStatus
    {
        if ($record->isLate()) {
            return AttendanceStatus::LATE;
        }

        try {
            $rules = $this->ruleService->getApplicableRules($record->getEmployeeId(), $checkTime);
            $shifts = $rules['shifts'] ?? [];

            if (!is_array($shifts) || 0 === count($shifts)) {
                return AttendanceStatus::NORMAL;
            }

            foreach ($shifts as $shift) {
                if (!$shift instanceof WorkShift || !$shift->isActive()) {
                    continue;
                }

                $status = $this->calculateCheckOutStatusForShift($shift, $checkTime);
                if (AttendanceStatus::NORMAL !== $status) {
                    return $status;
                }
            }
        } catch (\Exception) {
            // Fallback to normal status on any error
        }

        return AttendanceStatus::NORMAL;
    }

    private function calculateCheckInStatusForShift(WorkShift $shift, \DateTimeInterface $checkTime, ?AttendanceGroup $group): AttendanceStatus
    {
        $startTime = $shift->getStartTime();

        if ($this->isWithinFlexibleTime($group, $shift, $checkTime, $startTime)) {
            return AttendanceStatus::NORMAL;
        }

        if ($this->isLateCheckIn($checkTime, $startTime)) {
            return AttendanceStatus::LATE;
        }

        return AttendanceStatus::NORMAL;
    }

    private function calculateCheckOutStatusForShift(WorkShift $shift, \DateTimeInterface $checkTime): AttendanceStatus
    {
        $endTime = $shift->getEndTime();

        if ($this->isEarlyCheckOut($checkTime, $endTime)) {
            return AttendanceStatus::EARLY;
        }

        if ($this->isOvertime($checkTime, $endTime)) {
            return AttendanceStatus::OVERTIME;
        }

        return AttendanceStatus::NORMAL;
    }

    private function isWithinFlexibleTime(?AttendanceGroup $group, WorkShift $shift, \DateTimeInterface $checkTime, \DateTimeInterface $startTime): bool
    {
        if (null === $group || !$group->isFlexibleTime()) {
            return false;
        }

        $flexibleMinutes = $shift->getFlexibleMinutes();
        if (null === $flexibleMinutes || $flexibleMinutes <= 0) {
            return false;
        }

        $startDateTime = \DateTime::createFromInterface($startTime);
        $flexStart = (clone $startDateTime)->modify("-{$flexibleMinutes} minutes");
        $flexEnd = (clone $startDateTime)->modify("+{$flexibleMinutes} minutes");

        return $checkTime >= $flexStart && $checkTime <= $flexEnd;
    }

    private function isLateCheckIn(\DateTimeInterface $checkTime, \DateTimeInterface $startTime): bool
    {
        return $checkTime->format('H:i') > $startTime->format('H:i')
               && $this->calculateLateMinutes($checkTime, $startTime) > 0;
    }

    private function isEarlyCheckOut(\DateTimeInterface $checkTime, \DateTimeInterface $endTime): bool
    {
        return $checkTime->format('H:i') < $endTime->format('H:i')
               && $this->calculateEarlyMinutes($checkTime, $endTime) > 0;
    }

    private function isOvertime(\DateTimeInterface $checkTime, \DateTimeInterface $endTime): bool
    {
        return $this->calculateOvertimeMinutes($checkTime, $endTime) > 30;
    }

    private function calculateLateMinutes(\DateTimeInterface $checkTime, \DateTimeInterface $startTime): int
    {
        return $this->calculateMinutesDifference($checkTime, $startTime);
    }

    private function calculateEarlyMinutes(\DateTimeInterface $checkTime, \DateTimeInterface $endTime): int
    {
        return $this->calculateMinutesDifference($endTime, $checkTime);
    }

    private function calculateOvertimeMinutes(\DateTimeInterface $checkTime, \DateTimeInterface $endTime): int
    {
        return $this->calculateMinutesDifference($checkTime, $endTime);
    }

    private function calculateMinutesDifference(\DateTimeInterface $later, \DateTimeInterface $earlier): int
    {
        $laterMinutes = (int) $later->format('H') * 60 + (int) $later->format('i');
        $earlierMinutes = (int) $earlier->format('H') * 60 + (int) $earlier->format('i');

        return max(0, $laterMinutes - $earlierMinutes);
    }
}
