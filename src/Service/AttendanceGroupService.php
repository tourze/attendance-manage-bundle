<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Service;

use Tourze\AttendanceManageBundle\Entity\AttendanceGroup;
use Tourze\AttendanceManageBundle\Enum\AttendanceGroupType;
use Tourze\AttendanceManageBundle\Exception\AttendanceException;
use Tourze\AttendanceManageBundle\Repository\AttendanceGroupRepository;
use Tourze\LockServiceBundle\Service\LockService;

class AttendanceGroupService
{
    public function __construct(
        private AttendanceGroupRepository $repository,
        private LockService $lockService,
    ) {
    }

    /**
     * @param array<string, mixed> $rules
     * @param array<int> $memberIds
     */
    public function createGroup(string $name, AttendanceGroupType $type, array $rules = [], array $memberIds = []): AttendanceGroup
    {
        if (!$this->validateGroupRules($rules)) {
            throw AttendanceException::invalidCheckIn('考勤组规则验证失败');
        }

        $group = new AttendanceGroup();
        $group->setName($name);
        $group->setType($type);
        $group->setRules($rules);
        $group->setMemberIds($memberIds);
        $this->repository->save($group, true);

        return $group;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateGroup(int $groupId, array $data): AttendanceGroup
    {
        $group = $this->repository->find($groupId);
        if (!$group instanceof AttendanceGroup) {
            throw AttendanceException::recordNotFound($groupId);
        }

        $this->updateGroupName($group, $data);
        $this->updateGroupType($group, $data);
        $this->updateGroupRules($group, $data);
        $this->updateGroupMemberIds($group, $data);

        $this->repository->save($group, true);

        return $group;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateGroupName(AttendanceGroup $group, array $data): void
    {
        if (!isset($data['name'])) {
            return;
        }

        if (!is_string($data['name'])) {
            throw AttendanceException::invalidCheckIn('考勤组名称必须是字符串');
        }

        $group->setName($data['name']);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateGroupType(AttendanceGroup $group, array $data): void
    {
        if (!isset($data['type'])) {
            return;
        }

        if (!is_int($data['type']) && !is_string($data['type'])) {
            throw AttendanceException::invalidCheckIn('考勤组类型格式错误');
        }

        $group->setType(AttendanceGroupType::from($data['type']));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateGroupRules(AttendanceGroup $group, array $data): void
    {
        if (!isset($data['rules'])) {
            return;
        }

        if (!is_array($data['rules'])) {
            throw AttendanceException::invalidCheckIn('考勤组规则必须是数组');
        }

        /** @var array<string, mixed> $rules */
        $rules = $data['rules'];
        if (!$this->validateGroupRules($rules)) {
            throw AttendanceException::invalidCheckIn('考勤组规则验证失败');
        }

        $group->setRules($rules);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateGroupMemberIds(AttendanceGroup $group, array $data): void
    {
        if (!isset($data['memberIds'])) {
            return;
        }

        if (!is_array($data['memberIds'])) {
            throw AttendanceException::invalidCheckIn('成员ID列表必须是数组');
        }

        /** @var array<int> $memberIds */
        $memberIds = $data['memberIds'];
        $group->setMemberIds($memberIds);
    }

    /**
     * @param array<int> $employeeIds
     */
    public function assignEmployees(int $groupId, array $employeeIds): void
    {
        $lockKey = sprintf('attendance_group_assign_%d', $groupId);

        $this->lockService->blockingRun($lockKey, function () use ($groupId, $employeeIds) {
            $group = $this->repository->find($groupId);
            if (!$group instanceof AttendanceGroup) {
                throw AttendanceException::recordNotFound($groupId);
            }

            foreach ($employeeIds as $employeeId) {
                $group->addMember($employeeId);
            }

            $this->repository->save($group, true);
        });
    }

    /**
     * @param array<int> $employeeIds
     */
    public function removeEmployees(int $groupId, array $employeeIds): void
    {
        $group = $this->repository->find($groupId);
        if (!$group instanceof AttendanceGroup) {
            throw AttendanceException::recordNotFound($groupId);
        }

        foreach ($employeeIds as $employeeId) {
            $group->removeMember($employeeId);
        }

        $this->repository->save($group, true);
    }

    public function getGroupById(int $groupId): ?AttendanceGroup
    {
        return $this->repository->find($groupId);
    }

    /**
     * @return array<AttendanceGroup>
     */
    public function getActiveGroups(): array
    {
        return $this->repository->findBy(['isActive' => true], ['name' => 'ASC']);
    }

    public function getEmployeeGroup(int $employeeId): ?AttendanceGroup
    {
        return $this->repository->findByMember($employeeId);
    }

    public function deactivateGroup(int $groupId): void
    {
        $group = $this->repository->find($groupId);
        if (!$group instanceof AttendanceGroup) {
            throw AttendanceException::recordNotFound($groupId);
        }

        $group->setActive(false);
        $this->repository->save($group, true);
    }

    /**
     * @param array<string, mixed> $rules
     */
    public function validateGroupRules(array $rules): bool
    {
        if (0 === count($rules)) {
            return true;
        }

        if (isset($rules['work_hours']) && $rules['work_hours'] < 0) {
            return false;
        }

        if (isset($rules['start_time'], $rules['end_time'])) {
            if (!is_string($rules['start_time']) || !is_string($rules['end_time'])) {
                return false;
            }
            $startTime = \DateTime::createFromFormat('H:i', $rules['start_time']);
            $endTime = \DateTime::createFromFormat('H:i', $rules['end_time']);

            if (false === $startTime || false === $endTime) {
                return false;
            }
        }

        return true;
    }
}
