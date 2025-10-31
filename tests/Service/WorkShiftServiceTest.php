<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\AttendanceManageBundle\Entity\WorkShift;
use Tourze\AttendanceManageBundle\Exception\AttendanceException;
use Tourze\AttendanceManageBundle\Exception\WorkShiftException;
use Tourze\AttendanceManageBundle\Repository\WorkShiftRepository;
use Tourze\AttendanceManageBundle\Service\WorkShiftService;

/**
 * @internal
 */
#[CoversClass(WorkShiftService::class)]
class WorkShiftServiceTest extends TestCase
{
    private TestWorkShiftRepository $repository;

    private WorkShiftService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // 清理静态数据，确保测试之间的独立性
        TestWorkShiftRepository::clearAll();

        // 使用简单的测试存根，避免 Mock 依赖
        $this->repository = new TestWorkShiftRepository();
        $this->service = new WorkShiftService($this->repository);
    }

    public function testCreateShift(): void
    {
        $groupId = 1;
        $name = '早班';
        $shiftData = [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'flexible_minutes' => 30,
            'break_times' => [['start' => '12:00', 'end' => '13:00']],
            'cross_day' => false,
        ];

        // 设置无冲突的重叠班次
        $this->repository->setOverlappingShifts([]);

        $shift = $this->service->createShift($groupId, $name, $shiftData);

        $this->assertInstanceOf(WorkShift::class, $shift);
        $this->assertSame($name, $shift->getName());
        $this->assertSame($groupId, $shift->getGroupId());
    }

    public function testCreateShiftWithConflict(): void
    {
        $groupId = 1;
        $name = '冲突班次';
        $shiftData = [
            'start_time' => '09:00',
            'end_time' => '18:00',
        ];

        $startTime = \DateTimeImmutable::createFromFormat('H:i', '09:00');
        $endTime = \DateTimeImmutable::createFromFormat('H:i', '18:00');
        if (false === $startTime || false === $endTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $existingShift = new WorkShift();
        $existingShift->setGroupId($groupId);
        $existingShift->setName('现有班次');
        $existingShift->setStartTime($startTime);
        $existingShift->setEndTime($endTime);

        // 设置有冲突的重叠班次
        $this->repository->setOverlappingShifts([$existingShift]);

        $this->expectException(AttendanceException::class);
        $this->service->createShift($groupId, $name, $shiftData);
    }

    public function testUpdateShift(): void
    {
        $shiftId = 1;
        $startTime2 = \DateTimeImmutable::createFromFormat('H:i', '09:00');
        $endTime2 = \DateTimeImmutable::createFromFormat('H:i', '18:00');
        if (false === $startTime2 || false === $endTime2) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $existingShift = new WorkShift();
        $existingShift->setGroupId(1);
        $existingShift->setName('原班次');
        $existingShift->setStartTime($startTime2);
        $existingShift->setEndTime($endTime2);
        // 手动设置 ID
        $reflection = new \ReflectionClass($existingShift);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($existingShift, $shiftId);

        $updateData = ['name' => '新班次名', 'flexible_minutes' => 60];

        // 预先添加实体到仓库
        $this->repository->setStoredEntities([$existingShift]);

        $updatedShift = $this->service->updateShift($shiftId, $updateData);

        $this->assertSame('新班次名', $updatedShift->getName());
        $this->assertSame(60, $updatedShift->getFlexibleMinutes());
    }

    public function testUpdateShiftNotFound(): void
    {
        // 不需要预先添加实体，find 方法将返回 null

        $this->expectException(AttendanceException::class);
        $this->service->updateShift(999, ['name' => '新班次']);
    }

    public function testValidateShiftData(): void
    {
        $validData = [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'flexible_minutes' => 30,
        ];

        $this->assertTrue($this->service->validateShiftData($validData));
    }

    public function testValidateShiftDataInvalid(): void
    {
        $invalidData = [
            'start_time' => 'invalid-time',
            'end_time' => '18:00',
        ];

        $this->assertFalse($this->service->validateShiftData($invalidData));
    }

    public function testCalculateWorkHours(): void
    {
        $testStartTime = \DateTimeImmutable::createFromFormat('H:i', '09:00');
        $testEndTime = \DateTimeImmutable::createFromFormat('H:i', '18:00');
        if (false === $testStartTime || false === $testEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $shift = new WorkShift();
        $shift->setGroupId(1);
        $shift->setName('标准班次');
        $shift->setStartTime($testStartTime);
        $shift->setEndTime($testEndTime);
        $shift->setFlexibleMinutes(30);
        $shift->setBreakTimes([['start' => '12:00', 'end' => '13:00']]);  // 1小时休息
        $shift->setCrossDay(false);

        $workHours = $this->service->calculateWorkHours($shift);

        // 9小时总时长 - 1小时休息 = 8小时工作时长
        $this->assertEquals(8.0, $workHours);
    }

    public function testCalculateWorkHoursCrossDay(): void
    {
        $nightStartTime = \DateTimeImmutable::createFromFormat('H:i', '22:00');
        $nightEndTime = \DateTimeImmutable::createFromFormat('H:i', '06:00');
        if (false === $nightStartTime || false === $nightEndTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $shift = new WorkShift();
        $shift->setGroupId(1);
        $shift->setName('跨天班次');
        $shift->setStartTime($nightStartTime);
        $shift->setEndTime($nightEndTime);
        $shift->setFlexibleMinutes(0);
        $shift->setBreakTimes([]);
        $shift->setCrossDay(true);

        $workHours = $this->service->calculateWorkHours($shift);

        // 22:00到次日06:00 = 8小时
        $this->assertEquals(8.0, $workHours);
    }

    public function testCheckShiftConflict(): void
    {
        $groupId = 1;
        $startTime = \DateTimeImmutable::createFromFormat('H:i', '09:00');
        $endTime = \DateTimeImmutable::createFromFormat('H:i', '18:00');
        if (false === $startTime || false === $endTime) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $startTime3 = \DateTimeImmutable::createFromFormat('H:i', '10:00');
        $endTime3 = \DateTimeImmutable::createFromFormat('H:i', '19:00');
        if (false === $startTime3 || false === $endTime3) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $existingShift = new WorkShift();
        $existingShift->setGroupId($groupId);
        $existingShift->setName('现有班次');
        $existingShift->setStartTime($startTime3);
        $existingShift->setEndTime($endTime3);

        // 设置有冲突的重叠班次
        $this->repository->setOverlappingShifts([$existingShift]);

        $hasConflict = $this->service->checkShiftConflict($groupId, $startTime, $endTime);

        $this->assertTrue($hasConflict);
    }

    public function testDeleteShift(): void
    {
        $shiftId = 1;
        $startTime4 = \DateTimeImmutable::createFromFormat('H:i', '09:00');
        $endTime4 = \DateTimeImmutable::createFromFormat('H:i', '18:00');
        if (false === $startTime4 || false === $endTime4) {
            throw WorkShiftException::timeCreationFailed('时间格式创建失败');
        }

        $existingShift = new WorkShift();
        $existingShift->setGroupId(1);
        $existingShift->setName('待删除班次');
        $existingShift->setStartTime($startTime4);
        $existingShift->setEndTime($endTime4);
        // 手动设置 ID
        $reflection = new \ReflectionClass($existingShift);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($existingShift, $shiftId);

        // 预先添加实体到仓库
        $this->repository->setStoredEntities([$existingShift]);

        $this->service->deleteShift($shiftId);

        $this->assertFalse($existingShift->isActive());
    }

    public function testDeleteShiftNotFound(): void
    {
        // 不需要预先添加实体，find 方法将返回 null

        $this->expectException(AttendanceException::class);
        $this->service->deleteShift(999);
    }
}
