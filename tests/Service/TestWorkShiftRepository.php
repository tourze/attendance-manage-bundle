<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Service;

use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Tourze\AttendanceManageBundle\Entity\WorkShift;
use Tourze\AttendanceManageBundle\Repository\WorkShiftRepository;

/**
 * 简单的测试存根，继承WorkShiftRepository以确保类型兼容性
 *
 * @phpstan-ignore-next-line
 */
class TestWorkShiftRepository extends WorkShiftRepository
{
    /**
     * @var WorkShift[]
     */
    private static array $entities = [];

    /**
     * @var WorkShift[]
     */
    private static array $overlappingShifts = [];

    public function __construct()
    {
        // 简化实现：创建一个最小的 ManagerRegistry mock
        $registry = $this->createMockRegistry();
        parent::__construct($registry);
    }

    private function createMockRegistry(): ManagerRegistry
    {
        return new class implements ManagerRegistry {
            public function getDefaultConnectionName(): string
            {
                return 'default';
            }

            public function getConnection(?string $name = null): object
            {
                throw new \RuntimeException('Test implementation');
            }

            public function getConnections(): array
            {
                return [];
            }

            public function getConnectionNames(): array
            {
                return [];
            }

            public function getDefaultManagerName(): string
            {
                return 'default';
            }

            public function getManager(?string $name = null): ObjectManager
            {
                throw new \RuntimeException('Test implementation');
            }

            public function getManagers(): array
            {
                return [];
            }

            public function resetManager(?string $name = null): ObjectManager
            {
                throw new \RuntimeException('Test implementation');
            }

            public function getAliasNamespace(string $alias): string
            {
                throw new \RuntimeException('Test implementation');
            }

            public function getManagerNames(): array
            {
                return [];
            }

            public function getRepository(string $persistentObject, ?string $persistentManagerName = null): ObjectRepository
            {
                throw new \RuntimeException('Test implementation');
            }

            public function getManagerForClass(string $class): ?ObjectManager
            {
                return null;
            }
        };
    }

    public function save(WorkShift $entity, bool $flush = false): void
    {
        self::$entities[] = $entity;
    }

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?WorkShift
    {
        foreach (self::$entities as $entity) {
            if ($entity->getId() === $id) {
                return $entity;
            }
        }

        return null;
    }

    /** @return WorkShift[] */
    public function findOverlappingShifts(int $groupId, \DateTimeInterface $startTime, \DateTimeInterface $endTime, ?int $excludeId = null): array
    {
        return self::$overlappingShifts;
    }

    /**
     * @param WorkShift[] $entities
     */
    public function setStoredEntities(array $entities): void
    {
        self::$entities = $entities;
    }

    /**
     * @param WorkShift[] $shifts
     */
    public function setOverlappingShifts(array $shifts): void
    {
        self::$overlappingShifts = $shifts;
    }

    public static function clearAll(): void
    {
        self::$entities = [];
        self::$overlappingShifts = [];
    }
}
