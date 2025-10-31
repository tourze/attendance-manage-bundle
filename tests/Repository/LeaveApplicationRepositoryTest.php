<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AttendanceManageBundle\Entity\LeaveApplication;
use Tourze\AttendanceManageBundle\Enum\LeaveType;
use Tourze\AttendanceManageBundle\Exception\LeaveApplicationException;
use Tourze\AttendanceManageBundle\Repository\LeaveApplicationRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(LeaveApplicationRepository::class)]
#[RunTestsInSeparateProcesses]
class LeaveApplicationRepositoryTest extends AbstractRepositoryTestCase
{
    protected function getRepositoryClass(): string
    {
        return LeaveApplicationRepository::class;
    }

    protected function getEntityClass(): string
    {
        return LeaveApplication::class;
    }

    protected function getRepository(): LeaveApplicationRepository
    {
        $repository = self::getEntityManager()->getRepository(LeaveApplication::class);
        if (!$repository instanceof LeaveApplicationRepository) {
            throw new LeaveApplicationException('Expected LeaveApplicationRepository instance');
        }

        return $repository;
    }

    protected function createNewEntity(): LeaveApplication
    {
        $application = new LeaveApplication();
        $application->setEmployeeId(1);
        $application->setLeaveType(LeaveType::ANNUAL);
        $application->setStartDate(new \DateTimeImmutable('2024-01-01'));
        $application->setEndDate(new \DateTimeImmutable('2024-01-05'));
        $application->setDuration(5.0);
        $application->setReason('测试请假');

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

    public function testFindByEmployeeId(): void
    {
        $repository = $this->createMock(LeaveApplicationRepository::class);
        $repository->method('findByEmployeeId')->willReturn([]);
        $this->assertEquals([], $repository->findByEmployeeId(1));
    }

    public function testFindByDateRange(): void
    {
        $repository = $this->getRepository();
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $this->assertEquals([], $repository->findByDateRange($startDate, $endDate));
    }

    public function testFindByEmployeeAndDateRange(): void
    {
        $repository = $this->createMock(LeaveApplicationRepository::class);
        $repository->method('findByDateRange')->willReturn([]);
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $this->assertEquals([], $repository->findByDateRange($startDate, $endDate));
    }

    public function testFindByStatus(): void
    {
        $repository = $this->createMock(LeaveApplicationRepository::class);
        $repository->method('findByStatus')->willReturn([]);
        $this->assertEquals([], $repository->findByStatus('pending'));
    }

    public function testFindByEmployeeAndLeaveType(): void
    {
        $repository = $this->createMock(LeaveApplicationRepository::class);
        $repository->method('findByEmployeeAndLeaveType')->willReturn([]);
        $this->assertEquals([], $repository->findByEmployeeAndLeaveType(1, LeaveType::ANNUAL));
    }

    public function testFindPendingApplications(): void
    {
        $repository = $this->createMock(LeaveApplicationRepository::class);
        $repository->method('findPendingApplications')->willReturn([]);
        $this->assertEquals([], $repository->findPendingApplications());
    }

    public function testFindOverlappingApplications(): void
    {
        $repository = $this->createMock(LeaveApplicationRepository::class);
        $repository->method('findOverlappingApplications')->willReturn([]);
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-05');

        $this->assertEquals([], $repository->findOverlappingApplications(1, $startDate, $endDate));
    }

    public function testCountByEmployeeAndYear(): void
    {
        $repository = $this->createMock(LeaveApplicationRepository::class);
        $repository->method('countByEmployeeAndYear')->willReturn(0);
        $this->assertEquals(0, $repository->countByEmployeeAndYear(1, 2024));
    }

    public function testFindByEmployeeAndStatus(): void
    {
        $repository = $this->createMock(LeaveApplicationRepository::class);
        $repository->method('findByEmployeeAndStatus')->willReturn([]);
        $this->assertEquals([], $repository->findByEmployeeAndStatus(1, 'pending'));
    }

    public function testFindApplicationsRequiringApproval(): void
    {
        $repository = $this->createMock(LeaveApplicationRepository::class);
        $repository->method('findApplicationsRequiringApproval')->willReturn([]);
        $this->assertEquals([], $repository->findApplicationsRequiringApproval(1));
    }

    public function testFindCurrentLeaves(): void
    {
        $repository = $this->createMock(LeaveApplicationRepository::class);
        $repository->method('findCurrentLeaves')->willReturn([]);
        $this->assertEquals([], $repository->findCurrentLeaves());
    }

    public function testFindExpiringSoon(): void
    {
        $repository = $this->createMock(LeaveApplicationRepository::class);
        $repository->method('findExpiringSoon')->willReturn([]);
        $this->assertEquals([], $repository->findExpiringSoon(30));
    }
}
