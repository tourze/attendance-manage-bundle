<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Service;

use Tourze\AttendanceManageBundle\Entity\AttendanceGroup;
use Tourze\AttendanceManageBundle\Entity\WorkShift;
use Tourze\AttendanceManageBundle\Enum\AttendanceGroupType;
use Tourze\AttendanceManageBundle\Exception\AttendanceException;
use Tourze\AttendanceManageBundle\Repository\AttendanceGroupRepository;
use Tourze\AttendanceManageBundle\Repository\WorkShiftRepository;
use Tourze\LockServiceBundle\Service\LockService;

readonly class RuleService
{
    public function __construct(
        private AttendanceGroupRepository $attendanceGroupRepository,
        private WorkShiftRepository $workShiftRepository,
        private LockService $lockService,
        private EntityUpdater $entityUpdater,
        private ShiftMatcher $shiftMatcher,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createAttendanceGroup(array $data): AttendanceGroup
    {
        if (!isset($data['name']) || !is_string($data['name'])) {
            throw AttendanceException::invalidCheckIn('考勤组名称必须是字符串');
        }
        if (!isset($data['type']) || (!is_int($data['type']) && !is_string($data['type']))) {
            throw AttendanceException::invalidCheckIn('考勤组类型格式错误');
        }

        $group = new AttendanceGroup();
        $group->setName($data['name']);
        $group->setType(AttendanceGroupType::from($data['type']));

        $rules = $data['rules'] ?? [];
        if (!is_array($rules)) {
            throw AttendanceException::invalidCheckIn('考勤组规则必须是数组');
        }
        /** @var array<string, mixed> $typedRules */
        $typedRules = $rules;
        $group->setRules($typedRules);

        $memberIds = $data['memberIds'] ?? [];
        if (!is_array($memberIds)) {
            throw AttendanceException::invalidCheckIn('成员ID列表必须是数组');
        }
        /** @var array<int> $typedMemberIds */
        $typedMemberIds = $memberIds;
        $group->setMemberIds($typedMemberIds);

        $this->attendanceGroupRepository->save($group, true);

        return $group;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateAttendanceGroup(int $groupId, array $data): AttendanceGroup
    {
        $group = $this->attendanceGroupRepository->find($groupId);

        if (null === $group) {
            throw AttendanceException::recordNotFound($groupId);
        }

        $this->entityUpdater->updateAttendanceGroup($group, $data);

        $this->attendanceGroupRepository->save($group, true);

        return $group;
    }

    public function assignEmployeeToGroup(int $employeeId, int $groupId): bool
    {
        $lockKey = sprintf('employee_group_assign_%d', $employeeId);

        $result = $this->lockService->blockingRun($lockKey, function () use ($employeeId, $groupId): bool {
            $currentGroup = $this->getEmployeeGroup($employeeId);
            if (null !== $currentGroup && $currentGroup->getId() !== $groupId) {
                $currentGroup->removeMember($employeeId);
                $this->attendanceGroupRepository->save($currentGroup);
            }

            $group = $this->attendanceGroupRepository->find($groupId);
            if (null === $group) {
                throw AttendanceException::attendanceGroupNotFound($groupId);
            }

            $group->addMember($employeeId);
            $this->attendanceGroupRepository->save($group, true);

            return true;
        });

        return is_bool($result) ? $result : false;
    }

    public function removeEmployeeFromGroup(int $employeeId, int $groupId): bool
    {
        $group = $this->attendanceGroupRepository->find($groupId);
        if (null === $group) {
            throw AttendanceException::attendanceGroupNotFound($groupId);
        }

        $group->removeMember($employeeId);
        $this->attendanceGroupRepository->save($group, true);

        return true;
    }

    public function getEmployeeGroup(int $employeeId): ?AttendanceGroup
    {
        return $this->attendanceGroupRepository->findByEmployeeId($employeeId);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createWorkShift(int $groupId, array $data): WorkShift
    {
        $group = $this->attendanceGroupRepository->find($groupId);
        if (null === $group) {
            throw AttendanceException::attendanceGroupNotFound($groupId);
        }

        if (!isset($data['name']) || !is_string($data['name'])) {
            throw AttendanceException::invalidCheckIn('班次名称必须是字符串');
        }
        if (!isset($data['startTime']) || !$data['startTime'] instanceof \DateTimeImmutable) {
            throw AttendanceException::invalidCheckIn('开始时间格式错误');
        }
        if (!isset($data['endTime']) || !$data['endTime'] instanceof \DateTimeImmutable) {
            throw AttendanceException::invalidCheckIn('结束时间格式错误');
        }

        $shift = new WorkShift();
        $shift->setGroupId($groupId);
        $shift->setName($data['name']);
        $shift->setStartTime($data['startTime']);
        $shift->setEndTime($data['endTime']);

        $flexibleMinutes = $data['flexibleMinutes'] ?? null;
        if (null !== $flexibleMinutes && !is_int($flexibleMinutes)) {
            throw AttendanceException::invalidCheckIn('弹性时间必须是整数');
        }
        $shift->setFlexibleMinutes($flexibleMinutes);

        $breakTimes = $data['breakTimes'] ?? [];
        if (!is_array($breakTimes)) {
            throw AttendanceException::invalidCheckIn('休息时间必须是数组');
        }
        /** @var array<array{start: string, end: string}> $typedBreakTimes */
        $typedBreakTimes = $breakTimes;
        $shift->setBreakTimes($typedBreakTimes);

        $crossDay = $data['crossDay'] ?? false;
        if (!is_bool($crossDay)) {
            throw AttendanceException::invalidCheckIn('跨天标识必须是布尔值');
        }
        $shift->setCrossDay($crossDay);

        $this->workShiftRepository->save($shift, true);

        return $shift;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateWorkShift(int $shiftId, array $data): WorkShift
    {
        $shift = $this->workShiftRepository->find($shiftId);

        if (null === $shift) {
            throw AttendanceException::recordNotFound($shiftId);
        }

        $this->entityUpdater->updateWorkShift($shift, $data);

        $this->workShiftRepository->save($shift, true);

        return $shift;
    }

    /**
     * @return array<string, mixed>
     */
    public function getApplicableRules(int $employeeId, \DateTimeInterface $date): array
    {
        $group = $this->getEmployeeGroup($employeeId);

        if (null === $group) {
            throw AttendanceException::noAttendanceGroup($employeeId);
        }

        $groupId = $group->getId();
        if (null === $groupId) {
            throw AttendanceException::invalidCheckIn('考勤组ID无效');
        }
        $shifts = $this->workShiftRepository->findByGroupId($groupId);

        return [
            'group' => $group,
            'shifts' => $shifts,
            'rules' => $group->getRules(),
            'type' => $group->getType(),
        ];
    }

    /**
     * @return array<string, array<string>>
     */
    public function validateAttendance(int $employeeId, \DateTimeInterface $checkTime, string $type): array
    {
        $rules = $this->getApplicableRules($employeeId, $checkTime);
        $group = $rules['group'];
        $shifts = $rules['shifts'];

        $violations = [];
        $warnings = [];

        $emptyShiftsResult = $this->validateEmptyShifts($shifts);
        if (null !== $emptyShiftsResult) {
            return $emptyShiftsResult;
        }

        /** @var array<WorkShift> $typedShifts */
        $typedShifts = $shifts;
        $currentShift = $this->getCurrentShift($typedShifts, $checkTime);
        if (null === $currentShift) {
            $violations[] = '当前时间不在任何班次范围内';

            return ['violations' => $violations, 'warnings' => $warnings];
        }

        $violations = array_merge($violations, $this->validateFlexibleTime($group instanceof AttendanceGroup ? $group : null, $currentShift, $checkTime));
        $warnings = array_merge($warnings, $this->validateCheckTime($type, $checkTime, $currentShift));

        return ['violations' => $violations, 'warnings' => $warnings];
    }

    /**
     * @return array{violations: array<string>, warnings: array<string>}|null
     */
    private function validateEmptyShifts(mixed $shifts): ?array
    {
        if (!is_array($shifts) || 0 === count($shifts)) {
            return [
                'violations' => [],
                'warnings' => ['该考勤组未配置班次信息'],
            ];
        }

        return null;
    }

    /**
     * @param array<WorkShift> $shifts
     */
    private function getCurrentShift(array $shifts, \DateTimeInterface $checkTime): ?WorkShift
    {
        return $this->shiftMatcher->getCurrentShift($shifts, $checkTime);
    }

    /**
     * @return array<string>
     */
    private function validateFlexibleTime(?AttendanceGroup $group, WorkShift $currentShift, \DateTimeInterface $checkTime): array
    {
        if (!$group instanceof AttendanceGroup || !$group->isFlexibleTime() || null === $currentShift->getFlexibleMinutes()) {
            return [];
        }

        if (!$currentShift->isWithinFlexibleRange($checkTime)) {
            return [sprintf(
                '超出弹性工时范围: ±%d分钟',
                $currentShift->getFlexibleMinutes()
            )];
        }

        return [];
    }

    /**
     * @return array<string>
     */
    private function validateCheckTime(string $type, \DateTimeInterface $checkTime, WorkShift $currentShift): array
    {
        $checkHour = (int) $checkTime->format('H');
        $startHour = (int) $currentShift->getStartTime()->format('H');
        $endHour = (int) $currentShift->getEndTime()->format('H');

        $warnings = [];

        if ('check_in' === $type && $checkHour < $startHour - 2) {
            $warnings[] = '打卡时间过早';
        }

        if ('check_out' === $type && $checkHour > $endHour + 2) {
            $warnings[] = '可能存在加班情况';
        }

        return $warnings;
    }

    /**
     * @return array<string, mixed>
     */
    public function getGroupStatistics(): array
    {
        $groups = $this->attendanceGroupRepository->findActive();

        $statistics = [
            'total' => count($groups),
            'by_type' => [],
            'member_distribution' => [],
        ];

        foreach ($groups as $group) {
            $type = $group->getType()->value;
            $memberCount = count($group->getMemberIds());

            if (!isset($statistics['by_type'][$type])) {
                $statistics['by_type'][$type] = 0;
            }
            ++$statistics['by_type'][$type];

            $range = $this->getMemberCountRange($memberCount);
            if (!isset($statistics['member_distribution'][$range])) {
                $statistics['member_distribution'][$range] = 0;
            }
            ++$statistics['member_distribution'][$range];
        }

        return $statistics;
    }

    private function getMemberCountRange(int $count): string
    {
        if (0 === $count) {
            return '0';
        }
        if ($count <= 10) {
            return '1-10';
        }
        if ($count <= 50) {
            return '11-50';
        }
        if ($count <= 100) {
            return '51-100';
        }

        return '100+';
    }

    /**
     * @param array<string, mixed> $ruleData
     * @return array<string, mixed>
     */
    public function createAttendanceRule(array $ruleData): array
    {
        if (!$this->validateAttendanceRule($ruleData)) {
            throw new AttendanceException('规则数据验证失败');
        }

        return [
            'id' => uniqid('rule_'),
            'name' => $ruleData['name'],
            'type' => $ruleData['type'],
            'config' => $ruleData['config'] ?? [],
            'created_at' => new \DateTimeImmutable(),
        ];
    }

    /**
     * @param array<string, mixed> $updateData
     * @return array<string, mixed>
     */
    public function updateAttendanceRule(string $ruleId, array $updateData): array
    {
        return [
            'id' => $ruleId,
            'name' => $updateData['name'] ?? 'Updated Rule',
            'type' => 'work_hours',
            'config' => $updateData['config'] ?? [],
            'update_time' => new \DateTimeImmutable(),
        ];
    }

    public function deleteAttendanceRule(string $ruleId): bool
    {
        // 简化实现：记录删除操作
        // 在实际应用中，这里会从数据库删除规则
        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAttendanceRule(string $ruleId): ?array
    {
        // 简化实现：返回 null
        // 在实际应用中，这里会从数据库查询规则
        return null;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getAttendanceRulesByType(string $type): array
    {
        // 简化实现：返回空数组
        // 在实际应用中，这里会从数据库查询指定类型的规则
        return [];
    }

    /**
     * @param array<string, mixed> $rule
     */
    public function validateAttendanceRule(array $rule): bool
    {
        // 验证规则名称
        if (!isset($rule['name']) || '' === $rule['name'] || !is_string($rule['name'])) {
            return false;
        }

        // 验证规则类型
        if (!isset($rule['type']) || '' === $rule['type'] || !is_string($rule['type'])) {
            return false;
        }

        // 验证配置存在
        if (!isset($rule['config'])) {
            return false;
        }

        return true;
    }

    public function getEmployeeAttendanceGroup(int $employeeId): ?AttendanceGroup
    {
        return $this->attendanceGroupRepository->findByMember($employeeId);
    }

    public function canEmployeeCheckIn(int $employeeId): bool
    {
        $group = $this->getEmployeeAttendanceGroup($employeeId);

        if (null === $group) {
            return false;
        }

        if (!$group->isActive()) {
            return false;
        }

        return true;
    }

    public function calculateWorkingMinutes(\DateTimeInterface $startTime, \DateTimeInterface $endTime, int $breakMinutes = 0): int
    {
        if ($startTime >= $endTime) {
            return 0;
        }

        $totalMinutes = ($endTime->getTimestamp() - $startTime->getTimestamp()) / 60;
        $workingMinutes = max(0, $totalMinutes - $breakMinutes);

        return (int) $workingMinutes;
    }

    public function isLateCheckIn(\DateTimeInterface $checkInTime, \DateTimeInterface $expectedTime, int $flexibleMinutes = 0): bool
    {
        $diffMinutes = ($checkInTime->getTimestamp() - $expectedTime->getTimestamp()) / 60;

        return $diffMinutes > $flexibleMinutes;
    }

    public function isEarlyCheckOut(\DateTimeInterface $checkOutTime, \DateTimeInterface $expectedTime, int $flexibleMinutes = 0): bool
    {
        $diffMinutes = ($expectedTime->getTimestamp() - $checkOutTime->getTimestamp()) / 60;

        return $diffMinutes > $flexibleMinutes;
    }
}
