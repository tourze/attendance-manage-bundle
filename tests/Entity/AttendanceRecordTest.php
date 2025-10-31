<?php

declare(strict_types=1);

namespace TourzeAttendanceManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AttendanceManageBundle\Entity\AttendanceRecord;
use Tourze\AttendanceManageBundle\Enum\AttendanceStatus;
use Tourze\AttendanceManageBundle\Enum\CheckInType;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(AttendanceRecord::class)]
class AttendanceRecordTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $entity = new AttendanceRecord();
        $entity->setEmployeeId(1);
        $entity->setWorkDate(new \DateTimeImmutable('2024-01-15'));

        return $entity;
    }

    public static function propertiesProvider(): iterable
    {
        return [
            'workDate' => ['workDate', new \DateTimeImmutable('2024-01-15')],
            'checkInTime' => ['checkInTime', new \DateTimeImmutable('2024-01-15 09:00:00')],
            'checkOutTime' => ['checkOutTime', new \DateTimeImmutable('2024-01-15 18:00:00')],
            'checkInLocation' => ['checkInLocation', 'Office'],
            'checkOutLocation' => ['checkOutLocation', 'Home'],
            'status' => ['status', AttendanceStatus::NORMAL],
            'abnormalReason' => ['abnormalReason', 'test reason'],
        ];
    }

    public function testConstructor(): void
    {
        $employeeId = 1001;
        $workDate = new \DateTimeImmutable('2024-01-15');

        $record = new AttendanceRecord();
        $record->setEmployeeId($employeeId);
        $record->setWorkDate($workDate);

        $this->assertEquals($employeeId, $record->getEmployeeId());
        $this->assertEquals($workDate, $record->getWorkDate());
        $this->assertEquals(AttendanceStatus::NORMAL, $record->getStatus());
        $this->assertNull($record->getCheckInTime());
        $this->assertNull($record->getCheckOutTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $record->getCreateTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $record->getUpdateTime());
    }

    public function testCheckIn(): void
    {
        $record = new AttendanceRecord();
        $record->setEmployeeId(1001);
        $record->setWorkDate(new \DateTimeImmutable('2024-01-15'));
        $checkInTime = new \DateTimeImmutable('2024-01-15 09:00:00');
        $location = 'Office Building, Floor 5';

        $this->assertFalse($record->hasCheckIn());

        $record->checkIn($checkInTime, CheckInType::APP, $location);

        $this->assertTrue($record->hasCheckIn());
        $this->assertEquals($checkInTime, $record->getCheckInTime());
        $this->assertEquals(CheckInType::APP, $record->getCheckInType());
        $this->assertEquals($location, $record->getCheckInLocation());
    }

    public function testCheckOut(): void
    {
        $record = new AttendanceRecord();
        $record->setEmployeeId(1001);
        $record->setWorkDate(new \DateTimeImmutable('2024-01-15'));
        $checkOutTime = new \DateTimeImmutable('2024-01-15 18:00:00');

        $this->assertFalse($record->hasCheckOut());

        $record->checkOut($checkOutTime);

        $this->assertTrue($record->hasCheckOut());
        $this->assertEquals($checkOutTime, $record->getCheckOutTime());
    }

    public function testWorkDurationMinutes(): void
    {
        $record = new AttendanceRecord();
        $record->setEmployeeId(1001);
        $record->setWorkDate(new \DateTimeImmutable('2024-01-15'));

        $this->assertNull($record->getWorkDurationMinutes());

        $checkInTime = new \DateTimeImmutable('2024-01-15 09:00:00');
        $record->checkIn($checkInTime, CheckInType::APP);
        $this->assertNull($record->getWorkDurationMinutes());

        $checkOutTime = new \DateTimeImmutable('2024-01-15 18:00:00');
        $record->checkOut($checkOutTime);
        $this->assertEquals(540, $record->getWorkDurationMinutes());
    }

    public function testStatusCheckers(): void
    {
        $record = new AttendanceRecord();
        $record->setEmployeeId(1001);
        $record->setWorkDate(new \DateTimeImmutable('2024-01-15'));

        $this->assertTrue($record->isNormal());
        $this->assertFalse($record->isLate());
        $this->assertFalse($record->isEarlyLeave());
        $this->assertFalse($record->isAbsent());

        $record->setStatus(AttendanceStatus::LATE);
        $this->assertFalse($record->isNormal());
        $this->assertTrue($record->isLate());

        $record->setStatus(AttendanceStatus::EARLY);
        $this->assertTrue($record->isEarlyLeave());

        $record->setStatus(AttendanceStatus::ABSENT);
        $this->assertTrue($record->isAbsent());

        $record->setStatus(AttendanceStatus::LEAVE);
        $this->assertTrue($record->isOnLeave());

        $record->setStatus(AttendanceStatus::OVERTIME);
        $this->assertTrue($record->isOvertime());
    }

    public function testAbnormalHandling(): void
    {
        $record = new AttendanceRecord();
        $record->setEmployeeId(1001);
        $record->setWorkDate(new \DateTimeImmutable('2024-01-15'));
        $reason = 'Late due to traffic jam';

        $this->assertNull($record->getAbnormalReason());

        $record->markAsAbnormal($reason);
        $this->assertEquals($reason, $record->getAbnormalReason());

        $record->clearAbnormal();
        $this->assertNull($record->getAbnormalReason());
        $this->assertEquals(AttendanceStatus::NORMAL, $record->getStatus());
    }

    public function testIsComplete(): void
    {
        $record = new AttendanceRecord();
        $record->setEmployeeId(1001);
        $record->setWorkDate(new \DateTimeImmutable('2024-01-15'));

        $this->assertFalse($record->isComplete());

        $checkInTime = new \DateTimeImmutable('2024-01-15 09:00:00');
        $record->checkIn($checkInTime, CheckInType::APP);
        $this->assertFalse($record->isComplete());

        $checkOutTime = new \DateTimeImmutable('2024-01-15 18:00:00');
        $record->checkOut($checkOutTime);
        $this->assertTrue($record->isComplete());
    }
}
