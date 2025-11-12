<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Service;

use Tourze\AttendanceManageBundle\Entity\AttendanceGroup;
use Tourze\AttendanceManageBundle\Entity\WorkShift;
use Tourze\AttendanceManageBundle\Enum\AttendanceGroupType;

class EntityUpdater
{
    /**
     * @param array<string, mixed> $data
     */
    public function updateAttendanceGroup(AttendanceGroup $group, array $data): void
    {
        // 处理type字段的特殊转换
        if (array_key_exists('type', $data) && is_string($data['type'])) {
            $data['type'] = AttendanceGroupType::from($data['type']);
        }

        if (array_key_exists('name', $data) && is_string($data['name'])) {
            $group->setName($data['name']);
        }
        if (array_key_exists('type', $data) && $data['type'] instanceof AttendanceGroupType) {
            $group->setType($data['type']);
        }
        if (array_key_exists('rules', $data) && is_array($data['rules'])) {
            /** @var array<string, mixed> $typedRules */
            $typedRules = $data['rules'];
            $group->setRules($typedRules);
        }
        if (array_key_exists('memberIds', $data) && is_array($data['memberIds'])) {
            /** @var array<int> $typedMemberIds */
            $typedMemberIds = $data['memberIds'];
            $group->setMemberIds($typedMemberIds);
        }
        if (array_key_exists('isActive', $data) && is_bool($data['isActive'])) {
            $group->setActive($data['isActive']);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateWorkShift(WorkShift $shift, array $data): void
    {
        if (array_key_exists('name', $data) && is_string($data['name'])) {
            $shift->setName($data['name']);
        }
        if (array_key_exists('startTime', $data) && $data['startTime'] instanceof \DateTimeImmutable) {
            $shift->setStartTime($data['startTime']);
        }
        if (array_key_exists('endTime', $data) && $data['endTime'] instanceof \DateTimeImmutable) {
            $shift->setEndTime($data['endTime']);
        }
        if (array_key_exists('flexibleMinutes', $data) && (is_int($data['flexibleMinutes']) || $data['flexibleMinutes'] === null)) {
            $shift->setFlexibleMinutes($data['flexibleMinutes']);
        }
        if (array_key_exists('breakTimes', $data) && is_array($data['breakTimes'])) {
            /** @var array<array{start: string, end: string}> $typedBreakTimes */
            $typedBreakTimes = $data['breakTimes'];
            $shift->setBreakTimes($typedBreakTimes);
        }
        if (array_key_exists('crossDay', $data) && is_bool($data['crossDay'])) {
            $shift->setCrossDay($data['crossDay']);
        }
        if (array_key_exists('isActive', $data) && is_bool($data['isActive'])) {
            $shift->setActive($data['isActive']);
        }
    }
}