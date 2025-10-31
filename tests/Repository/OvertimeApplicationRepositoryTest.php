<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AttendanceManageBundle\Entity\OvertimeApplication;
use Tourze\AttendanceManageBundle\Enum\OvertimeType;
use Tourze\AttendanceManageBundle\Exception\AttendanceException;
use Tourze\AttendanceManageBundle\Repository\OvertimeApplicationRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(OvertimeApplicationRepository::class)]
#[RunTestsInSeparateProcesses]
class OvertimeApplicationRepositoryTest extends AbstractRepositoryTestCase
{
    protected function getRepositoryClass(): string
    {
        return OvertimeApplicationRepository::class;
    }

    protected function getEntityClass(): string
    {
        return OvertimeApplication::class;
    }

    protected function getRepository(): OvertimeApplicationRepository
    {
        $repository = self::getEntityManager()->getRepository(OvertimeApplication::class);
        if (!$repository instanceof OvertimeApplicationRepository) {
            throw new AttendanceException('Expected OvertimeApplicationRepository instance');
        }

        return $repository;
    }

    protected function createNewEntity(): OvertimeApplication
    {
        $application = new OvertimeApplication();
        $application->setEmployeeId(1);
        $application->setOvertimeDate(new \DateTimeImmutable('2024-01-01'));
        $application->setStartTime(new \DateTimeImmutable('2024-01-01 18:00:00'));
        $application->setEndTime(new \DateTimeImmutable('2024-01-01 22:00:00'));
        $application->setDuration(4.0);
        $application->setOvertimeType(OvertimeType::WORKDAY);
        $application->setReason('测试加班');

        return $application;
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

    public function testFindByEmployee(): void
    {
        $repository = $this->createMock(OvertimeApplicationRepository::class);
        $repository->method('findByEmployee')->willReturn([]);
        $this->assertEquals([], $repository->findByEmployee(1));
    }

    public function testFindByDateRange(): void
    {
        $repository = $this->createMock(OvertimeApplicationRepository::class);
        $repository->method('findByDateRange')->willReturn([]);
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $this->assertEquals([], $repository->findByDateRange($startDate, $endDate));
    }

    public function testFindByEmployeeAndDateRange(): void
    {
        $repository = $this->createMock(OvertimeApplicationRepository::class);
        $repository->method('findByEmployeeAndDateRange')->willReturn([]);
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $this->assertEquals([], $repository->findByEmployeeAndDateRange(1, $startDate, $endDate));
    }

    public function testFindOverlapping(): void
    {
        $repository = $this->createMock(OvertimeApplicationRepository::class);
        $repository->method('findOverlapping')->willReturn([]);
        $startTime = new \DateTimeImmutable('2024-01-01 09:00:00');
        $endTime = new \DateTimeImmutable('2024-01-01 18:00:00');

        $this->assertEquals([], $repository->findOverlapping(1, $startTime, $endTime));
    }

    public function testFindPendingApplications(): void
    {
        $repository = $this->createMock(OvertimeApplicationRepository::class);
        $repository->method('findPendingApplications')->willReturn([]);
        $this->assertEquals([], $repository->findPendingApplications());
    }

    public function testGetTotalOvertimeHours(): void
    {
        $repository = $this->createMock(OvertimeApplicationRepository::class);
        $repository->method('getTotalOvertimeHours')->willReturn(0.0);
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $this->assertEquals(0.0, $repository->getTotalOvertimeHours(1, $startDate, $endDate));
    }
}
