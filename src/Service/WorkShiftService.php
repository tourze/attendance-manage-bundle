<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Service;

use Tourze\AttendanceManageBundle\Entity\WorkShift;
use Tourze\AttendanceManageBundle\Exception\AttendanceException;
use Tourze\AttendanceManageBundle\Repository\WorkShiftRepository;

readonly class WorkShiftService
{
    public function __construct(
        private WorkShiftRepository $repository,
    ) {
    }

    /**
     * @param array<string, mixed> $shiftData
     */
    public function createShift(int $groupId, string $name, array $shiftData): WorkShift
    {
        if (!$this->validateShiftData($shiftData)) {
            throw AttendanceException::invalidCheckIn('班次数据验证失败');
        }

        $times = $this->parseShiftTimes($shiftData);
        $this->validateTimeConflict($groupId, $times['start'], $times['end']);

        $shift = new WorkShift();
        $shift->setGroupId($groupId);
        $shift->setName($name);
        $shift->setStartTime($times['start']);
        $shift->setEndTime($times['end']);
        $shift->setFlexibleMinutes($this->normalizeFlexibleMinutes($shiftData));
        $shift->setBreakTimes($this->normalizeBreakTimes($shiftData));
        $shift->setCrossDay($this->normalizeCrossDay($shiftData));

        $this->repository->save($shift, true);

        return $shift;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateShift(int $shiftId, array $data): WorkShift
    {
        $shift = $this->repository->find($shiftId);
        if (!$shift instanceof WorkShift) {
            throw AttendanceException::recordNotFound($shiftId);
        }

        $this->applyShiftUpdates($shift, $data);
        $this->repository->save($shift, true);

        return $shift;
    }

    public function deleteShift(int $shiftId): void
    {
        $shift = $this->repository->find($shiftId);
        if (!$shift instanceof WorkShift) {
            throw AttendanceException::recordNotFound($shiftId);
        }

        $shift->setActive(false);
        $this->repository->save($shift, true);
    }

    public function getShiftById(int $shiftId): ?WorkShift
    {
        return $this->repository->find($shiftId);
    }

    /**
     * @return WorkShift[]
     */
    public function getShiftsByGroup(int $groupId): array
    {
        return $this->repository->findByGroupId($groupId);
    }

    /**
     * @return WorkShift[]
     */
    public function getActiveShifts(): array
    {
        return $this->repository->findActive();
    }

    /**
     * @return WorkShift[]
     */
    public function getFlexibleShifts(): array
    {
        return $this->repository->findFlexibleShifts();
    }

    /**
     * @return WorkShift[]
     */
    public function getCrossDayShifts(): array
    {
        return $this->repository->findCrossDayShifts();
    }

    /**
     * @param array<string, mixed> $shiftData
     */
    public function validateShiftData(array $shiftData): bool
    {
        // 验证必需字段
        if (!isset($shiftData['start_time'], $shiftData['end_time'])) {
            return false;
        }

        // 验证时间格式
        $startTimeData = $shiftData['start_time'];
        $endTimeData = $shiftData['end_time'];

        if (!is_string($startTimeData) || !is_string($endTimeData)) {
            return false;
        }

        $startTime = \DateTime::createFromFormat('H:i', $startTimeData);
        $endTime = \DateTime::createFromFormat('H:i', $endTimeData);

        if (false === $startTime || false === $endTime) {
            return false;
        }

        // 验证弹性时间
        if (isset($shiftData['flexible_minutes']) && $shiftData['flexible_minutes'] < 0) {
            return false;
        }

        return true;
    }

    public function checkShiftConflict(int $groupId, \DateTimeInterface $startTime, \DateTimeInterface $endTime, ?int $excludeShiftId = null): bool
    {
        $overlappingShifts = $this->repository->findOverlappingShifts($groupId, $startTime, $endTime, $excludeShiftId);

        return count($overlappingShifts) > 0;
    }

    public function calculateWorkHours(WorkShift $shift): float
    {
        return $shift->getWorkDurationMinutes() / 60.0;
    }

    /**
     * 解析班次时间数据
     *
     * @param array<string, mixed> $shiftData
     * @return array<string, \DateTimeImmutable>
     */
    private function parseShiftTimes(array $shiftData): array
    {
        $startTimeString = $shiftData['start_time'] ?? '';
        $endTimeString = $shiftData['end_time'] ?? '';

        if (!is_string($startTimeString) || !is_string($endTimeString)) {
            throw AttendanceException::invalidCheckIn('时间格式无效');
        }

        $startTime = \DateTimeImmutable::createFromFormat('H:i', $startTimeString);
        $endTime = \DateTimeImmutable::createFromFormat('H:i', $endTimeString);

        if (false === $startTime || false === $endTime) {
            throw AttendanceException::invalidCheckIn('时间格式无效');
        }

        return ['start' => $startTime, 'end' => $endTime];
    }

    /**
     * 验证时间冲突
     */
    private function validateTimeConflict(int $groupId, \DateTimeInterface $startTime, \DateTimeInterface $endTime): void
    {
        if ($this->checkShiftConflict($groupId, $startTime, $endTime)) {
            throw AttendanceException::invalidCheckIn('班次时间与现有班次冲突');
        }
    }

    /**
     * 标准化弹性时间
     *
     * @param array<string, mixed> $shiftData
     */
    private function normalizeFlexibleMinutes(array $shiftData): int
    {
        $flexibleMinutes = $shiftData['flexible_minutes'] ?? 0;
        if (!is_int($flexibleMinutes)) {
            $flexibleMinutes = is_numeric($flexibleMinutes) ? (int) $flexibleMinutes : 0;
        }

        return $flexibleMinutes;
    }

    /**
     * 标准化休息时间
     *
     * @param array<string, mixed> $shiftData
     * @return array<array{start: string, end: string}>
     */
    private function normalizeBreakTimes(array $shiftData): array
    {
        $breakTimes = $shiftData['break_times'] ?? [];
        if (!is_array($breakTimes)) {
            return [];
        }

        $validatedBreakTimes = [];
        foreach ($breakTimes as $breakTime) {
            if (is_array($breakTime)
                && isset($breakTime['start'], $breakTime['end'])
                && is_string($breakTime['start'])
                && is_string($breakTime['end'])
            ) {
                $validatedBreakTimes[] = [
                    'start' => $breakTime['start'],
                    'end' => $breakTime['end'],
                ];
            }
        }

        return $validatedBreakTimes;
    }

    /**
     * 标准化跨天标志
     *
     * @param array<string, mixed> $shiftData
     */
    private function normalizeCrossDay(array $shiftData): bool
    {
        $crossDay = $shiftData['cross_day'] ?? false;
        if (!is_bool($crossDay)) {
            $crossDay = (bool) $crossDay;
        }

        return $crossDay;
    }

    /**
     * 应用班次更新
     *
     * @param array<string, mixed> $data
     */
    private function applyShiftUpdates(WorkShift $shift, array $data): void
    {
        if (isset($data['name']) && is_string($data['name'])) {
            $shift->setName($data['name']);
        }

        if (isset($data['flexible_minutes'])) {
            $flexibleValue = $data['flexible_minutes'];
            if (is_int($flexibleValue) || is_numeric($flexibleValue)) {
                $shift->setFlexibleMinutes((int) $flexibleValue);
            }
        }

        if (isset($data['break_times']) && is_array($data['break_times'])) {
            $shift->setBreakTimes($this->normalizeBreakTimes($data));
        }

        if (isset($data['cross_day'])) {
            $crossDayValue = $data['cross_day'];
            $shift->setCrossDay(is_bool($crossDayValue) ? $crossDayValue : (bool) $crossDayValue);
        }
    }
}
