<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Service;

use Tourze\AttendanceManageBundle\Entity\OvertimeApplication;
use Tourze\AttendanceManageBundle\Exception\AttendanceException;

class OvertimeService
{
    /**
     * @param array<string, mixed> $overtimeData
     */
    public function createOvertimeApplication(int $employeeId, array $overtimeData): OvertimeApplication
    {
        if (!$this->validateOvertimeData($overtimeData)) {
            throw AttendanceException::invalidCheckIn('加班申请数据验证失败');
        }

        if (!isset($overtimeData['overtime_date']) || !is_string($overtimeData['overtime_date'])) {
            throw AttendanceException::invalidCheckIn('加班日期格式错误');
        }

        if (!$this->canApplyOvertime($employeeId, new \DateTimeImmutable($overtimeData['overtime_date']))) {
            throw AttendanceException::invalidOvertime('不能申请该日期的加班');
        }

        // 简化实现 - 在真实项目中需要创建OvertimeApplication实体
        throw AttendanceException::invalidCheckIn('加班功能暂未完全实现');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateOvertimeApplication(int $applicationId, array $data): OvertimeApplication
    {
        throw AttendanceException::recordNotFound($applicationId);
    }

    public function approveOvertimeApplication(int $applicationId, int $approverId, ?string $comment = null): OvertimeApplication
    {
        throw AttendanceException::recordNotFound($applicationId);
    }

    public function rejectOvertimeApplication(int $applicationId, int $approverId, string $reason): OvertimeApplication
    {
        throw AttendanceException::recordNotFound($applicationId);
    }

    public function calculateOvertimePay(int $applicationId): float
    {
        // 基础倍率计算逻辑
        return 0.0;
    }

    public function calculateOvertimeHours(\DateTimeImmutable $startTime, \DateTimeImmutable $endTime): float
    {
        $interval = $startTime->diff($endTime);

        return $interval->h + ($interval->i / 60);
    }

    /**
     * @return OvertimeApplication[]
     */
    public function getOvertimeApplicationsByEmployee(int $employeeId, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        return [];
    }

    /**
     * @return OvertimeApplication[]
     */
    public function getPendingOvertimeApplications(): array
    {
        return [];
    }

    public function canApplyOvertime(int $employeeId, \DateTimeImmutable $overtimeDate): bool
    {
        // 基础规则：不能申请超过30天前的加班
        $thirtyDaysAgo = new \DateTimeImmutable('-30 days');

        return $overtimeDate >= $thirtyDaysAgo;
    }

    /**
     * @param array<string, mixed> $overtimeData
     */
    public function validateOvertimeData(array $overtimeData): bool
    {
        if (!isset($overtimeData['overtime_date'], $overtimeData['start_time'], $overtimeData['end_time'])) {
            return false;
        }

        if (!is_string($overtimeData['start_time']) || !is_string($overtimeData['end_time'])) {
            return false;
        }

        $startTime = \DateTimeImmutable::createFromFormat('H:i', $overtimeData['start_time']);
        $endTime = \DateTimeImmutable::createFromFormat('H:i', $overtimeData['end_time']);

        return false !== $startTime && false !== $endTime && $endTime > $startTime;
    }

    public function convertOvertimeToLeave(int $applicationId): bool
    {
        // 加班转调休功能
        return false;
    }

    public function getOvertimeMultiplier(\DateTimeImmutable $overtimeDate, string $overtimeType = 'normal'): float
    {
        // 工作日加班：1.5倍，周末：2.0倍，节假日：3.0倍
        $dayOfWeek = (int) $overtimeDate->format('N');

        if ($dayOfWeek >= 6) { // 周末
            return 2.0;
        }

        return match ($overtimeType) {
            'holiday' => 3.0,
            'weekend' => 2.0,
            default => 1.5,
        };
    }
}
