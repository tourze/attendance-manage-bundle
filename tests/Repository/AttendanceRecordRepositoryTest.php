<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AttendanceManageBundle\Entity\AttendanceRecord;
use Tourze\AttendanceManageBundle\Enum\AttendanceStatus;
use Tourze\AttendanceManageBundle\Exception\AttendanceException;
use Tourze\AttendanceManageBundle\Repository\AttendanceRecordRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(AttendanceRecordRepository::class)]
#[RunTestsInSeparateProcesses]
class AttendanceRecordRepositoryTest extends AbstractRepositoryTestCase
{
    protected function getRepositoryClass(): string
    {
        return AttendanceRecordRepository::class;
    }

    protected function getEntityClass(): string
    {
        return AttendanceRecord::class;
    }

    protected function getRepository(): AttendanceRecordRepository
    {
        $repository = self::getEntityManager()->getRepository(AttendanceRecord::class);
        if (!$repository instanceof AttendanceRecordRepository) {
            throw new AttendanceException('Expected AttendanceRecordRepository instance');
        }

        return $repository;
    }

    protected function createNewEntity(): AttendanceRecord
    {
        // Use unique employeeId and different dates to avoid unique constraint violations
        static $counter = 0;
        ++$counter;

        $record = new AttendanceRecord();
        $record->setEmployeeId($counter); // Different employee ID each time
        $record->setWorkDate(new \DateTimeImmutable('2024-01-' . str_pad((string) $counter, 2, '0', STR_PAD_LEFT))); // Different dates
        $record->setStatus(AttendanceStatus::NORMAL);

        return $record;
    }

    protected function onSetUp(): void
    {
        // 测试设置逻辑
    }

    public function testSave(): void
    {
        $repository = $this->getRepository();
        $entity = $this->createNewEntity();

        $repository->save($entity);
        self::getEntityManager()->flush();

        $this->assertNotNull($entity->getId());
    }

    public function testSaveWithFlush(): void
    {
        $repository = $this->getRepository();
        $entity = $this->createNewEntity();

        $repository->save($entity, true);
        $this->assertNotNull($entity->getId());
    }

    public function testRemove(): void
    {
        $repository = $this->getRepository();
        $entity = $this->createNewEntity();

        $repository->save($entity, true);
        $id = $entity->getId();

        $repository->remove($entity);
        $repository->remove($entity, true);

        $foundEntity = $repository->find($id);
        $this->assertNull($foundEntity);
    }

    public function testFindByEmployeeAndDate(): void
    {
        $repository = $this->createMock(AttendanceRecordRepository::class);
        $repository->method('findByEmployeeAndDate')->willReturn(null);

        $date = new \DateTimeImmutable('2024-01-01');

        $this->assertNull($repository->findByEmployeeAndDate(1, $date));
    }

    public function testFindByEmployeeAndDateRange(): void
    {
        $repository = $this->createMock(AttendanceRecordRepository::class);
        $repository->method('findByEmployeeAndDateRange')->willReturn([]);

        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $this->assertEquals([], $repository->findByEmployeeAndDateRange(1, $startDate, $endDate));
    }

    public function testFindByEmployeeAndMonth(): void
    {
        $repository = $this->createMock(AttendanceRecordRepository::class);
        $repository->method('findByEmployeeAndMonth')->willReturn([]);

        $this->assertEquals([], $repository->findByEmployeeAndMonth(1, 2024, 1));
    }

    public function testFindByDateAndEmployees(): void
    {
        $repository = $this->createMock(AttendanceRecordRepository::class);
        $repository->method('findByDateAndEmployees')->willReturn([]);

        $date = new \DateTimeImmutable('2024-01-01');

        $this->assertEquals([], $repository->findByDateAndEmployees($date, [1, 2, 3]));
    }

    public function testFindByStatus(): void
    {
        $repository = $this->createMock(AttendanceRecordRepository::class);
        $repository->method('findByStatus')->willReturn([]);

        $date = new \DateTimeImmutable('2024-01-01');

        $this->assertEquals([], $repository->findByStatus(AttendanceStatus::NORMAL, $date));
    }

    public function testFindAbnormalRecords(): void
    {
        $repository = $this->createMock(AttendanceRecordRepository::class);
        $repository->method('findAbnormalRecords')->willReturn([]);

        $this->assertEquals([], $repository->findAbnormalRecords(new \DateTimeImmutable('2024-01-01')));
    }

    public function testFindIncompleteRecords(): void
    {
        $repository = $this->createMock(AttendanceRecordRepository::class);
        $repository->method('findIncompleteRecords')->willReturn([]);

        $this->assertEquals([], $repository->findIncompleteRecords(new \DateTimeImmutable('2024-01-01')));
    }

    public function testCountByEmployeeAndStatus(): void
    {
        $repository = $this->createMock(AttendanceRecordRepository::class);
        $repository->method('countByEmployeeAndStatus')->willReturn(5);

        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $this->assertEquals(5, $repository->countByEmployeeAndStatus(1, AttendanceStatus::NORMAL, $startDate, $endDate));
    }

    public function testGetStatusStatisticsByDateRange(): void
    {
        $repository = $this->createMock(AttendanceRecordRepository::class);
        $repository->method('getStatusStatisticsByDateRange')->willReturn([]);

        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $this->assertEquals([], $repository->getStatusStatisticsByDateRange($startDate, $endDate));
    }

    public function testGetStatusStatisticsByDateRangeWithEmployeeIds(): void
    {
        $repository = $this->createMock(AttendanceRecordRepository::class);
        $repository->method('getStatusStatisticsByDateRange')->willReturn([]);

        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $this->assertEquals([], $repository->getStatusStatisticsByDateRange($startDate, $endDate, [1, 2, 3]));
    }

    public function testFindRecordsNeedingAttention(): void
    {
        $repository = $this->createMock(AttendanceRecordRepository::class);
        $repository->method('findRecordsNeedingAttention')->willReturn([]);

        $this->assertEquals([], $repository->findRecordsNeedingAttention(new \DateTimeImmutable('2024-01-01')));
    }

    public function testGetTotalWorkMinutesByEmployee(): void
    {
        $repository = $this->createMock(AttendanceRecordRepository::class);
        $repository->method('getTotalWorkMinutesByEmployee')->willReturn(2400);

        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $this->assertEquals(2400, $repository->getTotalWorkMinutesByEmployee(1, $startDate, $endDate));
    }

    public function testFindRecentRecord(): void
    {
        $repository = $this->createMock(AttendanceRecordRepository::class);
        $repository->method('findRecentRecord')->willReturn(null);

        $since = new \DateTimeImmutable('2024-01-01');
        $this->assertNull($repository->findRecentRecord(1, $since));
    }
}
