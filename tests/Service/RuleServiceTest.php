<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Service;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\AttendanceManageBundle\Entity\AttendanceGroup;
use Tourze\AttendanceManageBundle\Entity\WorkShift;
use Tourze\AttendanceManageBundle\Enum\AttendanceGroupType;
use Tourze\AttendanceManageBundle\Exception\AttendanceException;
use Tourze\AttendanceManageBundle\Repository\AttendanceGroupRepository;
use Tourze\AttendanceManageBundle\Repository\WorkShiftRepository;
use Tourze\AttendanceManageBundle\Service\EntityUpdater;
use Tourze\AttendanceManageBundle\Service\RuleService;
use Tourze\AttendanceManageBundle\Service\ShiftMatcher;
use Tourze\LockServiceBundle\Service\LockService;

/**
 * @internal
 */
#[CoversClass(RuleService::class)]
class RuleServiceTest extends TestCase
{
    private RuleService $service;

    private MockObject $repository;

    private MockObject $workShiftRepository;

    private MockObject $entityUpdater;

    private MockObject $shiftMatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(AttendanceGroupRepository::class);
        $this->workShiftRepository = $this->createMock(WorkShiftRepository::class);
        // 创建 LockService mock，配置 blockingRun 方法直接执行回调
        $lockService = $this->createMock(LockService::class);
        $lockService->method('blockingRun')
            ->willReturnCallback(static function ($lockKey, $callback) {
                if (!is_callable($callback)) {
                    throw new \InvalidArgumentException('Callback must be callable');
                }

                return $callback();
            })
        ;
        $this->entityUpdater = $this->createMock(EntityUpdater::class);
        $this->shiftMatcher = $this->createMock(ShiftMatcher::class);
        $this->service = new RuleService($this->repository, $this->workShiftRepository, $lockService, $this->entityUpdater, $this->shiftMatcher);
    }

    public function testCreateAttendanceRule(): void
    {
        $ruleData = [
            'name' => '标准工时规则',
            'type' => 'work_hours',
            'config' => [
                'daily_hours' => 8,
                'weekly_hours' => 40,
                'flexible_minutes' => 30,
            ],
        ];

        $rule = $this->service->createAttendanceRule($ruleData);

        $this->assertIsArray($rule);
        $this->assertEquals('标准工时规则', $rule['name']);
        $this->assertEquals('work_hours', $rule['type']);
        $this->assertIsArray($rule['config']);
        $this->assertEquals(8, $rule['config']['daily_hours']);
    }

    public function testCreateAttendanceRuleInvalidData(): void
    {
        $ruleData = [
            'name' => '', // 空名称
            'type' => 'work_hours',
        ];

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessage('规则数据验证失败');

        $this->service->createAttendanceRule($ruleData);
    }

    public function testUpdateAttendanceRule(): void
    {
        $ruleId = 'rule_001';
        $updateData = [
            'name' => '更新后的工时规则',
            'config' => [
                'daily_hours' => 7.5,
            ],
        ];

        $rule = $this->service->updateAttendanceRule($ruleId, $updateData);

        $this->assertIsArray($rule);
        $this->assertEquals('更新后的工时规则', $rule['name']);
        $this->assertIsArray($rule['config']);
        $this->assertEquals(7.5, $rule['config']['daily_hours']);
    }

    public function testDeleteAttendanceRule(): void
    {
        $ruleId = 'rule_001';

        // 删除操作不应该抛出异常
        $result = $this->service->deleteAttendanceRule($ruleId);

        // 验证删除成功
        $this->assertTrue($result);

        // 验证规则已被删除（尝试获取会返回null）
        $deletedRule = $this->service->getAttendanceRule($ruleId);
        $this->assertNull($deletedRule);
    }

    public function testGetAttendanceRule(): void
    {
        $ruleId = 'rule_001';

        $rule = $this->service->getAttendanceRule($ruleId);

        $this->assertNull($rule); // 当前简化实现返回null
    }

    public function testGetAttendanceRulesByType(): void
    {
        $type = 'work_hours';

        $rules = $this->service->getAttendanceRulesByType($type);

        $this->assertIsArray($rules);
        $this->assertEmpty($rules); // 当前简化实现返回空数组
    }

    public function testValidateAttendanceRule(): void
    {
        $rule = [
            'name' => '标准工时规则',
            'type' => 'work_hours',
            'config' => [
                'daily_hours' => 8,
                'weekly_hours' => 40,
            ],
        ];

        $isValid = $this->service->validateAttendanceRule($rule);

        $this->assertTrue($isValid);
    }

    public function testValidateAttendanceRuleInvalidName(): void
    {
        $rule = [
            'name' => '', // 空名称
            'type' => 'work_hours',
            'config' => [],
        ];

        $isValid = $this->service->validateAttendanceRule($rule);

        $this->assertFalse($isValid);
    }

    public function testValidateAttendanceRuleInvalidType(): void
    {
        $rule = [
            'name' => '工时规则',
            'type' => '', // 空类型
            'config' => [],
        ];

        $isValid = $this->service->validateAttendanceRule($rule);

        $this->assertFalse($isValid);
    }

    public function testValidateAttendanceRuleNoConfig(): void
    {
        $rule = [
            'name' => '工时规则',
            'type' => 'work_hours',
            // 缺少config
        ];

        $isValid = $this->service->validateAttendanceRule($rule);

        $this->assertFalse($isValid);
    }

    public function testAssignEmployeeToGroup(): void
    {
        $employeeId = 101;
        $groupId = 1;

        $group = new AttendanceGroup();
        $group->setName('技术部考勤组');
        $group->setType(AttendanceGroupType::FLEXIBLE);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($groupId)
            ->willReturn($group)
        ;

        $this->repository->expects($this->once())
            ->method('save')
            ->with($group, true)
        ;

        $result = $this->service->assignEmployeeToGroup($employeeId, $groupId);

        $this->assertTrue($result);
        $this->assertTrue($group->hasMember($employeeId));
    }

    public function testAssignEmployeeToGroupNotFound(): void
    {
        $employeeId = 101;
        $groupId = 999;

        $this->repository->expects($this->once())
            ->method('find')
            ->with($groupId)
            ->willReturn(null)
        ;

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessage('考勤组不存在');

        $this->service->assignEmployeeToGroup($employeeId, $groupId);
    }

    public function testRemoveEmployeeFromGroup(): void
    {
        $employeeId = 101;
        $groupId = 1;

        $group = new AttendanceGroup();
        $group->setName('技术部考勤组');
        $group->setType(AttendanceGroupType::FLEXIBLE);
        $group->setRules([]);
        $group->setMemberIds([101, 102, 103]);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($groupId)
            ->willReturn($group)
        ;

        $this->repository->expects($this->once())
            ->method('save')
            ->with($group, true)
        ;

        $result = $this->service->removeEmployeeFromGroup($employeeId, $groupId);

        $this->assertTrue($result);
        $this->assertFalse($group->hasMember($employeeId));
    }

    public function testRemoveEmployeeFromGroupNotFound(): void
    {
        $employeeId = 101;
        $groupId = 999;

        $this->repository->expects($this->once())
            ->method('find')
            ->with($groupId)
            ->willReturn(null)
        ;

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessage('考勤组不存在');

        $this->service->removeEmployeeFromGroup($employeeId, $groupId);
    }

    public function testGetEmployeeAttendanceGroup(): void
    {
        $employeeId = 101;

        $group = new AttendanceGroup();
        $group->setName('技术部考勤组');
        $group->setType(AttendanceGroupType::FLEXIBLE);
        $group->setRules([]);
        $group->setMemberIds([101, 102, 103]);

        $this->repository->expects($this->once())
            ->method('findByMember')
            ->with($employeeId)
            ->willReturn($group)
        ;

        $result = $this->service->getEmployeeAttendanceGroup($employeeId);

        $this->assertSame($group, $result);
    }

    public function testGetEmployeeAttendanceGroupNotFound(): void
    {
        $employeeId = 999;

        $this->repository->expects($this->once())
            ->method('findByMember')
            ->with($employeeId)
            ->willReturn(null)
        ;

        $result = $this->service->getEmployeeAttendanceGroup($employeeId);

        $this->assertNull($result);
    }

    public function testCanEmployeeCheckIn(): void
    {
        $employeeId = 101;
        $group = new AttendanceGroup();
        $group->setName('技术部考勤组');
        $group->setType(AttendanceGroupType::FLEXIBLE);
        $group->setRules([]);
        $group->setMemberIds([101, 102, 103]);

        $this->repository->expects($this->once())
            ->method('findByMember')
            ->with($employeeId)
            ->willReturn($group)
        ;

        $canCheckIn = $this->service->canEmployeeCheckIn($employeeId);

        $this->assertTrue($canCheckIn);
    }

    public function testCannotEmployeeCheckInNoGroup(): void
    {
        $employeeId = 999;

        $this->repository->expects($this->once())
            ->method('findByMember')
            ->with($employeeId)
            ->willReturn(null)
        ;

        $canCheckIn = $this->service->canEmployeeCheckIn($employeeId);

        $this->assertFalse($canCheckIn);
    }

    public function testCannotEmployeeCheckInInactiveGroup(): void
    {
        $employeeId = 101;
        $group = new AttendanceGroup();
        $group->setName('技术部考勤组');
        $group->setType(AttendanceGroupType::FLEXIBLE);
        $group->setRules([]);
        $group->setMemberIds([101, 102, 103]);
        $group->setActive(false); // 设置为非活跃状态

        $this->repository->expects($this->once())
            ->method('findByMember')
            ->with($employeeId)
            ->willReturn($group)
        ;

        $canCheckIn = $this->service->canEmployeeCheckIn($employeeId);

        $this->assertFalse($canCheckIn);
    }

    public function testCalculateWorkingMinutes(): void
    {
        $startTime = new \DateTimeImmutable('2025-08-15 09:00:00');
        $endTime = new \DateTimeImmutable('2025-08-15 18:00:00');
        $breakMinutes = 60;

        $workingMinutes = $this->service->calculateWorkingMinutes($startTime, $endTime, $breakMinutes);

        $this->assertEquals(480, $workingMinutes); // 9小时 - 1小时休息 = 8小时 = 480分钟
    }

    public function testCalculateWorkingMinutesNoBreak(): void
    {
        $startTime = new \DateTimeImmutable('2025-08-15 09:00:00');
        $endTime = new \DateTimeImmutable('2025-08-15 17:00:00');

        $workingMinutes = $this->service->calculateWorkingMinutes($startTime, $endTime);

        $this->assertEquals(480, $workingMinutes); // 8小时 = 480分钟
    }

    public function testCalculateWorkingMinutesZero(): void
    {
        $startTime = new \DateTimeImmutable('2025-08-15 09:00:00');
        $endTime = new \DateTimeImmutable('2025-08-15 09:00:00');

        $workingMinutes = $this->service->calculateWorkingMinutes($startTime, $endTime);

        $this->assertEquals(0, $workingMinutes);
    }

    public function testIsLateCheckIn(): void
    {
        $checkInTime = new \DateTimeImmutable('2025-08-15 09:30:00');
        $expectedTime = new \DateTimeImmutable('2025-08-15 09:00:00');
        $flexibleMinutes = 15;

        $isLate = $this->service->isLateCheckIn($checkInTime, $expectedTime, $flexibleMinutes);

        $this->assertTrue($isLate); // 迟到15分钟，超过弹性时间
    }

    public function testIsNotLateCheckIn(): void
    {
        $checkInTime = new \DateTimeImmutable('2025-08-15 09:10:00');
        $expectedTime = new \DateTimeImmutable('2025-08-15 09:00:00');
        $flexibleMinutes = 15;

        $isLate = $this->service->isLateCheckIn($checkInTime, $expectedTime, $flexibleMinutes);

        $this->assertFalse($isLate); // 迟到10分钟，在弹性时间内
    }

    public function testIsEarlyCheckOut(): void
    {
        $checkOutTime = new \DateTimeImmutable('2025-08-15 17:30:00');
        $expectedTime = new \DateTimeImmutable('2025-08-15 18:00:00');
        $flexibleMinutes = 15;

        $isEarly = $this->service->isEarlyCheckOut($checkOutTime, $expectedTime, $flexibleMinutes);

        $this->assertTrue($isEarly); // 早退30分钟，超过弹性时间
    }

    public function testIsNotEarlyCheckOut(): void
    {
        $checkOutTime = new \DateTimeImmutable('2025-08-15 17:50:00');
        $expectedTime = new \DateTimeImmutable('2025-08-15 18:00:00');
        $flexibleMinutes = 15;

        $isEarly = $this->service->isEarlyCheckOut($checkOutTime, $expectedTime, $flexibleMinutes);

        $this->assertFalse($isEarly); // 早退10分钟，在弹性时间内
    }

    public function testCreateAttendanceGroup(): void
    {
        $groupData = [
            'name' => '技术组',
            'type' => 'fixed',
            'rules' => [],
            'memberIds' => [],
        ];

        $this->repository->expects($this->once())
            ->method('save')
            ->with(Assert::isInstanceOf(AttendanceGroup::class), true)
        ;

        $result = $this->service->createAttendanceGroup($groupData);

        $this->assertInstanceOf(AttendanceGroup::class, $result);
        $this->assertEquals($groupData['name'], $result->getName());
        $this->assertEquals($groupData['type'], $result->getType()->value);
    }

    public function testUpdateAttendanceGroup(): void
    {
        $groupId = 1;
        $updateData = [
            'name' => '更新的技术组',
            'description' => '更新的描述',
        ];

        $existingGroup = new AttendanceGroup();
        $existingGroup->setName('技术组');
        $existingGroup->setType(AttendanceGroupType::FIXED);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($groupId)
            ->willReturn($existingGroup)
        ;

        $this->entityUpdater->expects($this->once())
            ->method('updateAttendanceGroup')
            ->with($existingGroup, $updateData)
            ->willReturnCallback(function ($group, $data) {
                if (!is_array($data)) {
                    return;
                }
                if (isset($data['name']) && $group instanceof AttendanceGroup && is_string($data['name'])) {
                    $group->setName($data['name']);
                }
            })
        ;

        $this->repository->expects($this->once())
            ->method('save')
            ->with($existingGroup, true)
        ;

        $result = $this->service->updateAttendanceGroup($groupId, $updateData);

        $this->assertInstanceOf(AttendanceGroup::class, $result);
        $this->assertEquals($updateData['name'], $result->getName());
    }

    public function testCreateWorkShift(): void
    {
        $groupId = 1;
        $shiftData = [
            'name' => '早班',
            'startTime' => new \DateTimeImmutable('08:00'),
            'endTime' => new \DateTimeImmutable('17:00'),
            'flexibleMinutes' => 30,
            'breakTimes' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'crossDay' => false,
        ];

        $group = new AttendanceGroup();
        $group->setName('技术组');
        $group->setType(AttendanceGroupType::FIXED);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($groupId)
            ->willReturn($group)
        ;

        $this->workShiftRepository->expects($this->once())
            ->method('save')
            ->with(Assert::anything(), true)
        ;

        $result = $this->service->createWorkShift($groupId, $shiftData);

        $this->assertInstanceOf(WorkShift::class, $result);
        $this->assertEquals($shiftData['name'], $result->getName());
    }

    public function testUpdateWorkShift(): void
    {
        $shiftId = 1;
        $updateData = [
            'name' => '更新的早班',
            'startTime' => new \DateTimeImmutable('08:30'),
        ];

        $existingShift = new WorkShift();
        $existingShift->setGroupId(1);
        $existingShift->setName('早班');
        $existingShift->setStartTime(new \DateTimeImmutable('08:00'));
        $existingShift->setEndTime(new \DateTimeImmutable('17:00'));
        $existingShift->setFlexibleMinutes(30);
        $existingShift->setBreakTimes([]);
        $existingShift->setCrossDay(false);

        $this->workShiftRepository->expects($this->once())
            ->method('find')
            ->with($shiftId)
            ->willReturn($existingShift)
        ;

        $this->workShiftRepository->expects($this->once())
            ->method('save')
            ->with($existingShift, true)
        ;

        $result = $this->service->updateWorkShift($shiftId, $updateData);

        $this->assertInstanceOf(WorkShift::class, $result);
    }
}
