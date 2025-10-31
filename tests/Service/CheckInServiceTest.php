<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Service;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\AttendanceManageBundle\Entity\AttendanceRecord;
use Tourze\AttendanceManageBundle\Enum\AttendanceStatus;
use Tourze\AttendanceManageBundle\Enum\CheckInType;
use Tourze\AttendanceManageBundle\Exception\AttendanceException;
use Tourze\AttendanceManageBundle\Repository\AttendanceRecordRepository;
use Tourze\AttendanceManageBundle\Service\AttendanceStatusCalculator;
use Tourze\AttendanceManageBundle\Service\CheckInService;
use Tourze\AttendanceManageBundle\Service\RuleService;

/**
 * @internal
 */
#[CoversClass(CheckInService::class)]
class CheckInServiceTest extends TestCase
{
    private CheckInService $service;

    private MockObject $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(AttendanceRecordRepository::class);
        $ruleService = $this->createMock(RuleService::class);
        $statusCalculator = $this->createMock(AttendanceStatusCalculator::class);

        // Configure status calculator to return normal status by default
        $statusCalculator->method('calculateCheckInStatus')
            ->willReturn(AttendanceStatus::NORMAL)
        ;
        $statusCalculator->method('calculateCheckOutStatus')
            ->willReturn(AttendanceStatus::NORMAL)
        ;

        $this->service = new CheckInService($this->repository, $ruleService, $statusCalculator);
    }

    public function testCheckIn(): void
    {
        $employeeId = 101;
        $type = CheckInType::APP;
        $data = ['location' => ['lat' => 39.9042, 'lng' => 116.4074]];

        $this->repository->expects($this->atLeastOnce())
            ->method('findByEmployeeAndDate')
            ->with($employeeId, Assert::anything())->willReturn(null)
        ;

        $this->repository->expects($this->atLeastOnce())
            ->method('save')
            ->with(Assert::isInstanceOf(AttendanceRecord::class), Assert::anything())
        ;

        $record = $this->service->checkIn($employeeId, $type, $data);

        $this->assertInstanceOf(AttendanceRecord::class, $record);
        $this->assertEquals($employeeId, $record->getEmployeeId());
        $this->assertNotNull($record->getCheckInTime());
        $this->assertEquals('39.904200,116.407400', $record->getCheckInLocation());
    }

    public function testCheckInAlreadyExists(): void
    {
        $employeeId = 101;
        $type = CheckInType::APP;
        $existingRecord = new AttendanceRecord();
        $existingRecord->setEmployeeId($employeeId);
        $existingRecord->setWorkDate(new \DateTimeImmutable('today'));
        $existingRecord->checkIn(new \DateTimeImmutable('09:00'), CheckInType::APP);

        $this->repository->expects($this->once())
            ->method('findByEmployeeAndDate')
            ->with($employeeId, Assert::anything())
            ->willReturn($existingRecord)
        ;

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessage('今日已有打卡记录');

        $this->service->checkIn($employeeId, $type);
    }

    public function testCheckOut(): void
    {
        $employeeId = 101;
        $type = CheckInType::APP;
        $data = ['location' => ['lat' => 39.9042, 'lng' => 116.4074]];

        $existingRecord = new AttendanceRecord();
        $existingRecord->setEmployeeId($employeeId);
        $existingRecord->setWorkDate(new \DateTimeImmutable('today'));
        $existingRecord->checkIn(new \DateTimeImmutable('09:00'), CheckInType::APP);

        $this->repository->expects($this->once())
            ->method('findByEmployeeAndDate')
            ->with($employeeId, Assert::anything())
            ->willReturn($existingRecord)
        ;

        $this->repository->expects($this->once())
            ->method('save')
            ->with($existingRecord, true)
        ;

        $record = $this->service->checkOut($employeeId, $type, $data);

        $this->assertInstanceOf(AttendanceRecord::class, $record);
        $this->assertNotNull($record->getCheckOutTime());
        $this->assertEquals('39.904200,116.407400', $record->getCheckOutLocation());
    }

    public function testCheckOutNoRecord(): void
    {
        $employeeId = 101;
        $type = CheckInType::APP;

        $this->repository->expects($this->once())
            ->method('findByEmployeeAndDate')
            ->with($employeeId, Assert::anything())
            ->willReturn(null)
        ;

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessage('请先打卡上班');

        $this->service->checkOut($employeeId, $type);
    }

    public function testCheckOutAlreadyCompleted(): void
    {
        $employeeId = 101;
        $type = CheckInType::APP;

        $existingRecord = new AttendanceRecord();
        $existingRecord->setEmployeeId($employeeId);
        $existingRecord->setWorkDate(new \DateTimeImmutable('today'));
        $existingRecord->checkIn(new \DateTimeImmutable('09:00'), CheckInType::APP);
        $existingRecord->checkOut(new \DateTimeImmutable('18:00'));

        $this->repository->expects($this->atLeastOnce())
            ->method('findByEmployeeAndDate')
            ->with($employeeId, Assert::anything())
            ->willReturn($existingRecord)
        ;

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessage('今日已签退');

        $this->service->checkOut($employeeId, $type);
    }

    public function testGetTodayRecord(): void
    {
        $employeeId = 101;
        $record = new AttendanceRecord();
        $record->setEmployeeId($employeeId);
        $record->setWorkDate(new \DateTimeImmutable('today'));

        $this->repository->expects($this->once())
            ->method('findByEmployeeAndDate')
            ->with($employeeId, Assert::anything())
            ->willReturn($record)
        ;

        $result = $this->service->getTodayRecord($employeeId);

        $this->assertSame($record, $result);
    }

    public function testGetTodayRecordNotFound(): void
    {
        $employeeId = 101;

        $this->repository->expects($this->once())
            ->method('findByEmployeeAndDate')
            ->with($employeeId, Assert::anything())
            ->willReturn(null)
        ;

        $result = $this->service->getTodayRecord($employeeId);

        $this->assertNull($result);
    }

    public function testCanCheckIn(): void
    {
        $employeeId = 101;

        $this->repository->expects($this->once())
            ->method('findByEmployeeAndDate')
            ->with($employeeId, Assert::anything())
            ->willReturn(null)
        ;

        $canCheckIn = $this->service->canCheckIn($employeeId);

        $this->assertTrue($canCheckIn);
    }

    public function testCannotCheckInAlreadyExists(): void
    {
        $employeeId = 101;
        $record = new AttendanceRecord();
        $record->setEmployeeId($employeeId);
        $record->setWorkDate(new \DateTimeImmutable('today'));
        $record->checkIn(new \DateTimeImmutable('09:00'), CheckInType::APP);

        $this->repository->expects($this->once())
            ->method('findByEmployeeAndDate')
            ->with($employeeId, Assert::anything())
            ->willReturn($record)
        ;

        $canCheckIn = $this->service->canCheckIn($employeeId);

        $this->assertFalse($canCheckIn);
    }

    public function testCanCheckOut(): void
    {
        $employeeId = 101;
        $record = new AttendanceRecord();
        $record->setEmployeeId($employeeId);
        $record->setWorkDate(new \DateTimeImmutable('today'));
        $record->checkIn(new \DateTimeImmutable('09:00'), CheckInType::APP);

        $this->repository->expects($this->once())
            ->method('findByEmployeeAndDate')
            ->with($employeeId, Assert::anything())
            ->willReturn($record)
        ;

        $canCheckOut = $this->service->canCheckOut($employeeId);

        $this->assertTrue($canCheckOut);
    }

    public function testCannotCheckOutNoRecord(): void
    {
        $employeeId = 101;

        $this->repository->expects($this->once())
            ->method('findByEmployeeAndDate')
            ->with($employeeId, Assert::anything())
            ->willReturn(null)
        ;

        $canCheckOut = $this->service->canCheckOut($employeeId);

        $this->assertFalse($canCheckOut);
    }

    public function testCannotCheckOutAlreadyCompleted(): void
    {
        $employeeId = 101;
        $record = new AttendanceRecord();
        $record->setEmployeeId($employeeId);
        $record->setWorkDate(new \DateTimeImmutable('today'));
        $record->checkIn(new \DateTimeImmutable('09:00'), CheckInType::APP);
        $record->checkOut(new \DateTimeImmutable('18:00'));

        $this->repository->expects($this->once())
            ->method('findByEmployeeAndDate')
            ->with($employeeId, Assert::anything())
            ->willReturn($record)
        ;

        $canCheckOut = $this->service->canCheckOut($employeeId);

        $this->assertFalse($canCheckOut);
    }

    public function testValidateLocationValid(): void
    {
        $location = ['lat' => 39.9042, 'lng' => 116.4074];

        $isValid = $this->service->validateLocation($location);

        $this->assertTrue($isValid);
    }

    public function testValidateLocationInvalid(): void
    {
        $location = ['lat' => 'invalid', 'lng' => 116.4074];

        $isValid = $this->service->validateLocation($location);

        $this->assertFalse($isValid);
    }

    public function testValidateLocationMissing(): void
    {
        $location = ['lat' => 39.9042];

        $isValid = $this->service->validateLocation($location);

        $this->assertFalse($isValid);
    }

    public function testValidateDevice(): void
    {
        $deviceId = 'DEVICE123';

        $isValid = $this->service->validateDevice($deviceId);

        $this->assertTrue($isValid);
    }

    public function testValidateDeviceEmpty(): void
    {
        $deviceId = '';

        $isValid = $this->service->validateDevice($deviceId);

        $this->assertFalse($isValid);
    }

    public function testPreventDuplicateCheckIn(): void
    {
        $employeeId = 101;
        $time = new \DateTimeImmutable();

        $this->repository->expects($this->once())
            ->method('findRecentRecord')
            ->with($employeeId, Assert::isInstanceOf(\DateTimeInterface::class))
            ->willReturn(null)
        ;

        $canPrevent = $this->service->preventDuplicateCheckIn($employeeId, $time);

        $this->assertTrue($canPrevent);
    }

    public function testPreventDuplicateCheckInFailed(): void
    {
        $employeeId = 101;
        $time = new \DateTimeImmutable();
        $record = new AttendanceRecord();
        $record->setEmployeeId($employeeId);
        $record->setWorkDate(new \DateTimeImmutable('today'));
        $record->checkIn($time->modify('-30 seconds'), CheckInType::APP); // 设置30秒前的签到时间

        $this->repository->expects($this->once())
            ->method('findRecentRecord')
            ->with($employeeId, Assert::isInstanceOf(\DateTimeInterface::class))
            ->willReturn($record)
        ;

        $canPrevent = $this->service->preventDuplicateCheckIn($employeeId, $time);

        $this->assertFalse($canPrevent);
    }
}
