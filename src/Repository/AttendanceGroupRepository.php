<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\AttendanceManageBundle\Entity\AttendanceGroup;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<AttendanceGroup>
 */
#[AsRepository(entityClass: AttendanceGroup::class)]
class AttendanceGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AttendanceGroup::class);
    }

    public function save(AttendanceGroup $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AttendanceGroup $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<AttendanceGroup>
     */
    public function findActive(): array
    {
        $result = $this->createQueryBuilder('ag')
            ->andWhere('ag.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('ag.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof AttendanceGroup ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    /**
     * @return array<AttendanceGroup>
     */
    public function findByType(string $type): array
    {
        $result = $this->createQueryBuilder('ag')
            ->andWhere('ag.type = :type')
            ->andWhere('ag.isActive = :active')
            ->setParameter('type', $type)
            ->setParameter('active', true)
            ->orderBy('ag.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof AttendanceGroup ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    public function findByEmployeeId(int $employeeId): ?AttendanceGroup
    {
        // SQLite doesn't support JSON_CONTAINS, so we fetch and filter in PHP
        /** @var AttendanceGroup[] $groups */
        $groups = $this->createQueryBuilder('ag')
            ->andWhere('ag.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult()
        ;

        foreach ($groups as $group) {
            if ($group->hasMember($employeeId)) {
                return $group;
            }
        }

        return null;
    }

    public function findByMember(int $employeeId): ?AttendanceGroup
    {
        return $this->findByEmployeeId($employeeId);
    }

    /**
     * @param int[] $employeeIds
     * @return AttendanceGroup[]
     */
    public function findByEmployeeIds(array $employeeIds): array
    {
        // SQLite doesn't support JSON_CONTAINS, so we fetch and filter in PHP
        /** @var AttendanceGroup[] $groups */
        $groups = $this->createQueryBuilder('ag')
            ->andWhere('ag.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult()
        ;

        $result = [];
        foreach ($groups as $group) {
            foreach ($employeeIds as $employeeId) {
                if ($group->hasMember($employeeId)) {
                    $result[] = $group;
                    break; // Found one match, no need to check other employeeIds for this group
                }
            }
        }

        return $result;
    }

    /**
     * @return array<AttendanceGroup>
     */
    public function findWithMembersCount(): array
    {
        // SQLite doesn't support JSON_LENGTH, so we just return groups
        // Member count can be calculated using count($group->getMemberIds())
        $result = $this->createQueryBuilder('ag')
            ->andWhere('ag.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('ag.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof AttendanceGroup ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    public function countByType(string $type): int
    {
        $result = $this->createQueryBuilder('ag')
            ->select('COUNT(ag.id)')
            ->andWhere('ag.type = :type')
            ->andWhere('ag.isActive = :active')
            ->setParameter('type', $type)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    /**
     * @return AttendanceGroup[]
     */
    public function findGroupsWithoutMembers(): array
    {
        // SQLite doesn't support JSON_LENGTH, so we fetch and filter in PHP
        /** @var AttendanceGroup[] $groups */
        $groups = $this->createQueryBuilder('ag')
            ->andWhere('ag.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('ag.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $result = [];
        foreach ($groups as $group) {
            if (0 === count($group->getMemberIds())) {
                $result[] = $group;
            }
        }

        return $result;
    }
}
