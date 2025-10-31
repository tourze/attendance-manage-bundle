<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\AttendanceManageBundle\Entity\AttendanceRecord;
use Tourze\AttendanceManageBundle\Enum\AttendanceStatus;
use Tourze\AttendanceManageBundle\Enum\CheckInType;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(AttendanceRecord::class)]
class AttendanceRecordValidationTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $entity = new AttendanceRecord();
        $entity->setEmployeeId(1001);
        $entity->setWorkDate(new \DateTimeImmutable('2024-08-09'));
        $entity->setStatus(AttendanceStatus::NORMAL);

        return $entity;
    }

    public static function propertiesProvider(): iterable
    {
        return [
            'checkInLocation' => ['checkInLocation', 'test_value'],
            'abnormalReason' => ['abnormalReason', 'test_value'],
        ];
    }

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;
    }

    public function testValidAttendanceRecord(): void
    {
        $record = new AttendanceRecord();
        $record->setEmployeeId(1001);
        $record->setWorkDate(new \DateTimeImmutable('2024-08-09'));
        $record->setStatus(AttendanceStatus::NORMAL);
        $record->setCheckInLocation('Office Building A');

        $violations = $this->validator->validate($record);
        $this->assertCount(0, $violations);
    }

    public function testInvalidEmployeeId(): void
    {
        $record = new AttendanceRecord();
        $record->setEmployeeId(0); // 无效的员工ID
        $record->setWorkDate(new \DateTimeImmutable('2024-08-09'));
        $record->setStatus(AttendanceStatus::NORMAL);

        $violations = $this->validator->validate($record);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testCheckInLocationTooLong(): void
    {
        $record = new AttendanceRecord();
        $record->setEmployeeId(1001);
        $record->setWorkDate(new \DateTimeImmutable('2024-08-09'));
        $record->setStatus(AttendanceStatus::NORMAL);

        $longLocation = str_repeat('A', 201); // 超过200字符
        $record->setCheckInLocation($longLocation);

        $violations = $this->validator->validate($record);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testAbnormalReasonTooLong(): void
    {
        $record = new AttendanceRecord();
        $record->setEmployeeId(1001);
        $record->setWorkDate(new \DateTimeImmutable('2024-08-09'));
        $record->setStatus(AttendanceStatus::LATE);

        $longReason = str_repeat('B', 501); // 超过500字符
        $record->markAsAbnormal($longReason);

        $violations = $this->validator->validate($record);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testValidEnumValues(): void
    {
        $record = new AttendanceRecord();
        $record->setEmployeeId(1001);
        $record->setWorkDate(new \DateTimeImmutable('2024-08-09'));
        $record->setStatus(AttendanceStatus::EARLY);
        $record->checkIn(new \DateTimeImmutable(), CheckInType::APP);

        $violations = $this->validator->validate($record);
        $this->assertCount(0, $violations);
    }
}
