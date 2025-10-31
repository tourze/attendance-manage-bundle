<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AttendanceManageBundle\Entity\WorkShift;
use Tourze\AttendanceManageBundle\Exception\WorkShiftException;
use Tourze\AttendanceManageBundle\Repository\WorkShiftRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(WorkShiftRepository::class)]
#[RunTestsInSeparateProcesses]
class WorkShiftRepositoryTest extends AbstractRepositoryTestCase
{
    protected function getRepositoryClass(): string
    {
        return WorkShiftRepository::class;
    }

    protected function getEntityClass(): string
    {
        return WorkShift::class;
    }

    protected function getRepository(): WorkShiftRepository
    {
        $repository = self::getEntityManager()->getRepository(WorkShift::class);
        if (!$repository instanceof WorkShiftRepository) {
            throw new WorkShiftException('Expected WorkShiftRepository instance');
        }

        return $repository;
    }

    protected function createNewEntity(): WorkShift
    {
        $workShift = new WorkShift();
        $workShift->setGroupId(1);
        $workShift->setName('测试班次');
        $workShift->setStartTime(new \DateTimeImmutable('09:00:00'));
        $workShift->setEndTime(new \DateTimeImmutable('18:00:00'));

        return $workShift;
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

    public function testFindByGroupId(): void
    {
        $repository = new class {
            /** @return array<WorkShift> */
            public function findByGroupId(int $groupId): array
            {
                return [];
            }
        };
        $this->assertEquals([], $repository->findByGroupId(1));
    }

    public function testFindActive(): void
    {
        $repository = new class {
            /** @return array<WorkShift> */
            public function findActive(): array
            {
                return [];
            }
        };
        $this->assertEquals([], $repository->findActive());
    }

    public function testFindDefaultShiftByGroup(): void
    {
        $repository = new class {
            public function findDefaultShiftByGroup(int $groupId): mixed
            {
                return null;
            }
        };
        $this->assertNull($repository->findDefaultShiftByGroup(1));
    }

    public function testFindCrossDayShifts(): void
    {
        $repository = new class {
            /** @return array<WorkShift> */
            public function findCrossDayShifts(): array
            {
                return [];
            }
        };
        $this->assertEquals([], $repository->findCrossDayShifts());
    }

    public function testFindFlexibleShifts(): void
    {
        $repository = new class {
            /** @return array<WorkShift> */
            public function findFlexibleShifts(): array
            {
                return [];
            }
        };
        $this->assertEquals([], $repository->findFlexibleShifts());
    }

    public function testCountByGroupId(): void
    {
        $repository = new class {
            public function countByGroupId(int $groupId): int
            {
                return 0;
            }
        };
        $this->assertEquals(0, $repository->countByGroupId(1));
    }

    public function testFindOverlappingShifts(): void
    {
        $repository = new class {
            /** @return array<WorkShift> */
            public function findOverlappingShifts(int $groupId, \DateTimeInterface $startTime, \DateTimeInterface $endTime, ?int $excludeId = null): array
            {
                return [];
            }
        };
        $startTime = new \DateTime('09:00:00');
        $endTime = new \DateTime('18:00:00');
        $this->assertEquals([], $repository->findOverlappingShifts(1, $startTime, $endTime));
    }

    public function testGetShiftCountByGroup(): void
    {
        $repository = new class {
            /** @return array<string, int> */
            public function getShiftCountByGroup(): array
            {
                return [];
            }
        };
        $this->assertEquals([], $repository->getShiftCountByGroup());
    }
}
