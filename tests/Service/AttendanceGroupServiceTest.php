<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Service;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\AttendanceManageBundle\Entity\AttendanceGroup;
use Tourze\AttendanceManageBundle\Enum\AttendanceGroupType;
use Tourze\AttendanceManageBundle\Exception\AttendanceException;
use Tourze\AttendanceManageBundle\Repository\AttendanceGroupRepository;
use Tourze\AttendanceManageBundle\Service\AttendanceGroupService;
use Tourze\LockServiceBundle\Service\LockService;

/**
 * @internal
 */
#[CoversClass(AttendanceGroupService::class)]
class AttendanceGroupServiceTest extends TestCase
{
    /** @var MockObject&AttendanceGroupRepository */
    private MockObject $repository;

    private AttendanceGroupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var MockObject&AttendanceGroupRepository $repository */
        $repository = $this->createMock(AttendanceGroupRepository::class);
        $this->repository = $repository;
        // 创建 LockService mock，配置 blockingRun 方法直接执行回调
        /** @var MockObject&LockService $lockService */
        $lockService = $this->createMock(LockService::class);
        $lockService->method('blockingRun')
            ->willReturnCallback(function (string $lockKey, callable $callback) {
                return $callback();
            })
        ;
        $this->service = new AttendanceGroupService($this->repository, $lockService);
    }

    public function testCreateGroup(): void
    {
        $name = '默认考勤组';
        $type = AttendanceGroupType::FIXED;
        $rules = ['work_hours' => 8];
        $memberIds = [1, 2, 3];

        $this->repository->expects($this->once())
            ->method('save')
            ->with(Assert::isInstanceOf(AttendanceGroup::class), true)
        ;

        $group = $this->service->createGroup($name, $type, $rules, $memberIds);

        $this->assertInstanceOf(AttendanceGroup::class, $group);
        $this->assertSame($name, $group->getName());
        $this->assertSame($type, $group->getType());
        $this->assertSame($rules, $group->getRules());
        $this->assertSame($memberIds, $group->getMemberIds());
    }

    public function testUpdateGroup(): void
    {
        $groupId = 1;
        $existingGroup = new AttendanceGroup();
        $existingGroup->setName('原组名');
        $existingGroup->setType(AttendanceGroupType::FIXED);
        $updateData = ['name' => '新组名', 'type' => 'flexible'];

        $this->repository->expects($this->once())
            ->method('find')
            ->with($groupId)
            ->willReturn($existingGroup)
        ;

        $this->repository->expects($this->once())
            ->method('save')
            ->with($existingGroup, true)
        ;

        $updatedGroup = $this->service->updateGroup($groupId, $updateData);

        $this->assertSame('新组名', $updatedGroup->getName());
        $this->assertSame(AttendanceGroupType::FLEXIBLE, $updatedGroup->getType());
    }

    public function testUpdateGroupNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null)
        ;

        $this->expectException(AttendanceException::class);
        $this->service->updateGroup(999, ['name' => '新组名']);
    }

    public function testAssignEmployees(): void
    {
        $groupId = 1;
        $employeeIds = [4, 5, 6];
        $existingGroup = new AttendanceGroup();
        $existingGroup->setName('测试组');
        $existingGroup->setType(AttendanceGroupType::FIXED);
        $existingGroup->setRules([]);
        $existingGroup->setMemberIds([1, 2, 3]);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($groupId)
            ->willReturn($existingGroup)
        ;

        $this->repository->expects($this->once())
            ->method('save')
            ->with($existingGroup, true)
        ;

        $this->service->assignEmployees($groupId, $employeeIds);

        $expectedMembers = [1, 2, 3, 4, 5, 6];
        $this->assertSame($expectedMembers, $existingGroup->getMemberIds());
    }

    public function testRemoveEmployees(): void
    {
        $groupId = 1;
        $removeIds = [2, 4];
        $existingGroup = new AttendanceGroup();
        $existingGroup->setName('测试组');
        $existingGroup->setType(AttendanceGroupType::FIXED);
        $existingGroup->setRules([]);
        $existingGroup->setMemberIds([1, 2, 3, 4, 5]);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($groupId)
            ->willReturn($existingGroup)
        ;

        $this->repository->expects($this->once())
            ->method('save')
            ->with($existingGroup, true)
        ;

        $this->service->removeEmployees($groupId, $removeIds);

        $expectedMembers = [1, 3, 5];
        $this->assertSame($expectedMembers, $existingGroup->getMemberIds());
    }

    public function testGetEmployeeGroup(): void
    {
        $employeeId = 123;
        $expectedGroup = new AttendanceGroup();
        $expectedGroup->setName('员工组');
        $expectedGroup->setType(AttendanceGroupType::FIXED);
        $expectedGroup->setRules([]);
        $expectedGroup->setMemberIds([$employeeId]);

        $this->repository->expects($this->once())
            ->method('findByMember')
            ->with($employeeId)
            ->willReturn($expectedGroup)
        ;

        $result = $this->service->getEmployeeGroup($employeeId);

        $this->assertSame($expectedGroup, $result);
    }

    public function testValidateGroupRules(): void
    {
        $validRules = [
            'work_hours' => 8,
            'start_time' => '09:00',
            'end_time' => '18:00',
        ];

        $this->assertTrue($this->service->validateGroupRules($validRules));
    }

    public function testValidateGroupRulesInvalid(): void
    {
        $invalidRules = [
            'work_hours' => -1,  // 负数小时无效
        ];

        $this->assertFalse($this->service->validateGroupRules($invalidRules));
    }

    public function testDeactivateGroup(): void
    {
        $groupId = 1;
        $existingGroup = new AttendanceGroup();
        $existingGroup->setName('测试组');
        $existingGroup->setType(AttendanceGroupType::FIXED);
        $existingGroup->setRules([]);
        $existingGroup->setMemberIds([1, 2, 3]);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($groupId)
            ->willReturn($existingGroup)
        ;

        $this->repository->expects($this->once())
            ->method('save')
            ->with($existingGroup, true)
        ;

        $this->service->deactivateGroup($groupId);

        $this->assertFalse($existingGroup->isActive());
    }

    public function testDeactivateGroupNotFound(): void
    {
        $groupId = 999;

        $this->repository->expects($this->once())
            ->method('find')
            ->with($groupId)
            ->willReturn(null)
        ;

        $this->expectException(AttendanceException::class);
        $this->service->deactivateGroup($groupId);
    }
}
