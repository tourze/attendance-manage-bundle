<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\AttendanceManageBundle\Entity\WorkShift;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<WorkShift>
 */
#[AsRepository(entityClass: WorkShift::class)]
class WorkShiftRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkShift::class);
    }

    public function save(WorkShift $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WorkShift $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return WorkShift[]
     */
    public function findByGroupId(int $groupId): array
    {
        $result = $this->createQueryBuilder('ws')
            ->andWhere('ws.groupId = :groupId')
            ->andWhere('ws.isActive = :active')
            ->setParameter('groupId', $groupId)
            ->setParameter('active', true)
            ->orderBy('ws.startTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof WorkShift ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    /**
     * @return WorkShift[]
     */
    public function findActive(): array
    {
        $result = $this->createQueryBuilder('ws')
            ->andWhere('ws.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('ws.groupId', 'ASC')
            ->addOrderBy('ws.startTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof WorkShift ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    public function findDefaultShiftByGroup(int $groupId): ?WorkShift
    {
        $result = $this->createQueryBuilder('ws')
            ->andWhere('ws.groupId = :groupId')
            ->andWhere('ws.isActive = :active')
            ->setParameter('groupId', $groupId)
            ->setParameter('active', true)
            ->orderBy('ws.startTime', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (null === $result) {
            return null;
        }

        if (!$result instanceof WorkShift) {
            throw new \RuntimeException('Invalid type');
        }

        return $result;
    }

    /**
     * @return WorkShift[]
     */
    public function findCrossDayShifts(): array
    {
        $result = $this->createQueryBuilder('ws')
            ->andWhere('ws.crossDay = :crossDay')
            ->andWhere('ws.isActive = :active')
            ->setParameter('crossDay', true)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof WorkShift ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    /**
     * @return WorkShift[]
     */
    public function findFlexibleShifts(): array
    {
        $result = $this->createQueryBuilder('ws')
            ->andWhere('ws.flexibleMinutes IS NOT NULL')
            ->andWhere('ws.flexibleMinutes > 0')
            ->andWhere('ws.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof WorkShift ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    public function countByGroupId(int $groupId): int
    {
        return (int) $this->createQueryBuilder('ws')
            ->select('COUNT(ws.id)')
            ->andWhere('ws.groupId = :groupId')
            ->andWhere('ws.isActive = :active')
            ->setParameter('groupId', $groupId)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @return array<int, int>
     */
    public function getShiftCountByGroup(): array
    {
        /** @var array<array{groupId: int, shiftCount: int}> $results */
        $results = $this->createQueryBuilder('ws')
            ->select('ws.groupId as groupId, COUNT(ws.id) as shiftCount')
            ->andWhere('ws.isActive = :active')
            ->setParameter('active', true)
            ->groupBy('ws.groupId')
            ->getQuery()
            ->getResult()
        ;

        $counts = [];
        foreach ($results as $result) {
            if (is_array($result)
                && isset($result['groupId'], $result['shiftCount'])
                && is_numeric($result['groupId'])
                && is_numeric($result['shiftCount'])
            ) {
                $counts[(int) $result['groupId']] = (int) $result['shiftCount'];
            }
        }

        return $counts;
    }

    /**
     * @return WorkShift[]
     */
    public function findOverlappingShifts(int $groupId, \DateTimeInterface $startTime, \DateTimeInterface $endTime, ?int $excludeId = null): array
    {
        $qb = $this->createQueryBuilder('ws')
            ->andWhere('ws.groupId = :groupId')
            ->andWhere('ws.isActive = :active')
            ->andWhere('ws.startTime < :endTime')
            ->andWhere('ws.endTime > :startTime')
            ->setParameter('groupId', $groupId)
            ->setParameter('active', true)
            ->setParameter('startTime', $startTime->format('H:i:s'))
            ->setParameter('endTime', $endTime->format('H:i:s'))
        ;

        if (null !== $excludeId) {
            $qb->andWhere('ws.id != :excludeId')
                ->setParameter('excludeId', $excludeId)
            ;
        }

        $result = $qb->getQuery()->getResult();

        return array_map(
            fn ($item) => $item instanceof WorkShift ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }
}
