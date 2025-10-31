<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\AttendanceManageBundle\Entity\OvertimeApplication;
use Tourze\AttendanceManageBundle\Exception\AttendanceException;
use Tourze\AttendanceManageBundle\Service\OvertimeService;

/**
 * @internal
 */
#[CoversClass(OvertimeService::class)]
class OvertimeServiceTest extends TestCase
{
    private OvertimeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new OvertimeService();
    }

    public function testCanApplyOvertime(): void
    {
        $employeeId = 101;
        $overtimeDate = new \DateTimeImmutable('-10 days'); // 10天前

        $canApply = $this->service->canApplyOvertime($employeeId, $overtimeDate);

        $this->assertTrue($canApply);
    }

    public function testCannotApplyOvertimeTooOld(): void
    {
        $employeeId = 101;
        $overtimeDate = new \DateTimeImmutable('-35 days'); // 35天前

        $canApply = $this->service->canApplyOvertime($employeeId, $overtimeDate);

        $this->assertFalse($canApply);
    }

    public function testValidateOvertimeDataValid(): void
    {
        $overtimeData = [
            'overtime_date' => '2025-08-15',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'reason' => '项目紧急需求',
        ];

        $isValid = $this->service->validateOvertimeData($overtimeData);

        $this->assertTrue($isValid);
    }

    public function testValidateOvertimeDataMissingFields(): void
    {
        $overtimeData = [
            'overtime_date' => '2025-08-15',
            // 缺少start_time和end_time
        ];

        $isValid = $this->service->validateOvertimeData($overtimeData);

        $this->assertFalse($isValid);
    }

    public function testValidateOvertimeDataInvalidTime(): void
    {
        $overtimeData = [
            'overtime_date' => '2025-08-15',
            'start_time' => 'invalid_time',
            'end_time' => '12:00',
        ];

        $isValid = $this->service->validateOvertimeData($overtimeData);

        $this->assertFalse($isValid);
    }

    public function testValidateOvertimeDataStartAfterEnd(): void
    {
        $overtimeData = [
            'overtime_date' => '2025-08-15',
            'start_time' => '15:00',
            'end_time' => '12:00', // 结束时间早于开始时间
        ];

        $isValid = $this->service->validateOvertimeData($overtimeData);

        $this->assertFalse($isValid);
    }

    public function testCalculateOvertimeHours(): void
    {
        $startTime = new \DateTimeImmutable('2025-08-15 09:00:00');
        $endTime = new \DateTimeImmutable('2025-08-15 12:30:00');

        $hours = $this->service->calculateOvertimeHours($startTime, $endTime);

        $this->assertEquals(3.5, $hours); // 3小时30分钟
    }

    public function testCalculateOvertimeHoursZero(): void
    {
        $startTime = new \DateTimeImmutable('2025-08-15 09:00:00');
        $endTime = new \DateTimeImmutable('2025-08-15 09:00:00');

        $hours = $this->service->calculateOvertimeHours($startTime, $endTime);

        $this->assertEquals(0.0, $hours);
    }

    public function testGetOvertimeMultiplierWeekday(): void
    {
        $overtimeDate = new \DateTimeImmutable('2025-08-18'); // 周一

        $multiplier = $this->service->getOvertimeMultiplier($overtimeDate, 'normal');

        $this->assertEquals(1.5, $multiplier);
    }

    public function testGetOvertimeMultiplierWeekend(): void
    {
        $overtimeDate = new \DateTimeImmutable('2025-08-16'); // 周六

        $multiplier = $this->service->getOvertimeMultiplier($overtimeDate, 'normal');

        $this->assertEquals(2.0, $multiplier); // 周末自动2倍
    }

    public function testGetOvertimeMultiplierSunday(): void
    {
        $overtimeDate = new \DateTimeImmutable('2025-08-17'); // 周日

        $multiplier = $this->service->getOvertimeMultiplier($overtimeDate, 'normal');

        $this->assertEquals(2.0, $multiplier); // 周末自动2倍
    }

    public function testGetOvertimeMultiplierHoliday(): void
    {
        $overtimeDate = new \DateTimeImmutable('2025-08-18'); // 工作日

        $multiplier = $this->service->getOvertimeMultiplier($overtimeDate, 'holiday');

        $this->assertEquals(3.0, $multiplier); // 节假日3倍
    }

    public function testGetOvertimeMultiplierWeekendType(): void
    {
        $overtimeDate = new \DateTimeImmutable('2025-08-18'); // 工作日

        $multiplier = $this->service->getOvertimeMultiplier($overtimeDate, 'weekend');

        $this->assertEquals(2.0, $multiplier); // 指定weekend类型2倍
    }

    public function testCalculateOvertimePayReturnsZero(): void
    {
        $applicationId = 1;

        $pay = $this->service->calculateOvertimePay($applicationId);

        $this->assertEquals(0.0, $pay); // 当前简化实现返回0
    }

    public function testConvertOvertimeToLeaveReturnsFalse(): void
    {
        $applicationId = 1;

        $result = $this->service->convertOvertimeToLeave($applicationId);

        $this->assertFalse($result); // 当前简化实现返回false
    }

    public function testGetOvertimeApplicationsByEmployeeReturnsEmpty(): void
    {
        $employeeId = 101;
        $startDate = new \DateTimeImmutable('2025-08-01');
        $endDate = new \DateTimeImmutable('2025-08-31');

        $applications = $this->service->getOvertimeApplicationsByEmployee($employeeId, $startDate, $endDate);

        $this->assertIsArray($applications);
        $this->assertEmpty($applications); // 当前简化实现返回空数组
    }

    public function testGetPendingOvertimeApplicationsReturnsEmpty(): void
    {
        $applications = $this->service->getPendingOvertimeApplications();

        $this->assertIsArray($applications);
        $this->assertEmpty($applications); // 当前简化实现返回空数组
    }

    public function testCreateOvertimeApplicationThrowsException(): void
    {
        $employeeId = 101;
        $overtimeData = [
            'overtime_date' => '2025-08-15',
            'start_time' => '09:00',
            'end_time' => '12:00',
        ];

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessage('不能申请该日期的加班');

        $this->service->createOvertimeApplication($employeeId, $overtimeData);
    }

    public function testCreateOvertimeApplicationInvalidData(): void
    {
        $employeeId = 101;
        $overtimeData = [
            'overtime_date' => '2025-08-15',
            // 缺少必需字段
        ];

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessage('加班申请数据验证失败');

        $this->service->createOvertimeApplication($employeeId, $overtimeData);
    }

    public function testCreateOvertimeApplicationCannotApply(): void
    {
        $employeeId = 101;
        $overtimeData = [
            'overtime_date' => '2025-01-01', // 很久以前的日期
            'start_time' => '09:00',
            'end_time' => '12:00',
        ];

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessage('不能申请该日期的加班');

        $this->service->createOvertimeApplication($employeeId, $overtimeData);
    }

    public function testUpdateOvertimeApplicationThrowsException(): void
    {
        $applicationId = 1;
        $data = ['reason' => '更新原因'];

        $this->expectException(AttendanceException::class);

        $this->service->updateOvertimeApplication($applicationId, $data);
    }

    public function testApproveOvertimeApplicationThrowsException(): void
    {
        $applicationId = 1;
        $approverId = 201;
        $comment = '同意加班申请';

        $this->expectException(AttendanceException::class);

        $this->service->approveOvertimeApplication($applicationId, $approverId, $comment);
    }

    public function testRejectOvertimeApplicationThrowsException(): void
    {
        $applicationId = 1;
        $approverId = 201;
        $reason = '拒绝原因';

        $this->expectException(AttendanceException::class);

        $this->service->rejectOvertimeApplication($applicationId, $approverId, $reason);
    }
}
