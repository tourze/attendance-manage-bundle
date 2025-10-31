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

        $this->applyChanges($group, $data, [
            'name' => 'setName',
            'type' => 'setType',
            'rules' => 'setRules',
            'memberIds' => 'setMemberIds',
            'isActive' => 'setActive',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateWorkShift(WorkShift $shift, array $data): void
    {
        $this->applyChanges($shift, $data, [
            'name' => 'setName',
            'startTime' => 'setStartTime',
            'endTime' => 'setEndTime',
            'flexibleMinutes' => 'setFlexibleMinutes',
            'breakTimes' => 'setBreakTimes',
            'crossDay' => 'setCrossDay',
            'isActive' => 'setActive',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $fieldMap
     */
    private function applyChanges(object $entity, array $data, array $fieldMap): void
    {
        foreach ($fieldMap as $field => $setter) {
            if (array_key_exists($field, $data)) {
                /** @phpstan-ignore-next-line */
                $entity->{$setter}($data[$field]);
            }
        }
    }
}
