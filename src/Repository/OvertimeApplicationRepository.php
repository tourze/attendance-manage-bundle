<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\AttendanceManageBundle\Entity\OvertimeApplication;
use Tourze\AttendanceManageBundle\Enum\ApplicationStatus;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<OvertimeApplication>
 */
#[AsRepository(entityClass: OvertimeApplication::class)]
class OvertimeApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OvertimeApplication::class);
    }

    public function save(OvertimeApplication $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OvertimeApplication $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return OvertimeApplication[]
     */
    public function findByEmployee(int $employeeId): array
    {
        $result = $this->createQueryBuilder('o')
            ->where('o.employeeId = :employeeId')
            ->setParameter('employeeId', $employeeId)
            ->orderBy('o.overtimeDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof OvertimeApplication ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    /**
     * @return OvertimeApplication[]
     */
    public function findPendingApplications(): array
    {
        $result = $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->setParameter('status', ApplicationStatus::PENDING->value)
            ->orderBy('o.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof OvertimeApplication ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    /**
     * @return OvertimeApplication[]
     */
    public function findByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $result = $this->createQueryBuilder('o')
            ->where('o.overtimeDate >= :startDate')
            ->andWhere('o.overtimeDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('o.overtimeDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof OvertimeApplication ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    /**
     * @return OvertimeApplication[]
     */
    public function findByEmployeeAndDateRange(
        int $employeeId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): array {
        $result = $this->createQueryBuilder('o')
            ->where('o.employeeId = :employeeId')
            ->andWhere('o.overtimeDate >= :startDate')
            ->andWhere('o.overtimeDate <= :endDate')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('o.overtimeDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof OvertimeApplication ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    /**
     * @return OvertimeApplication[]
     */
    public function findOverlapping(
        int $employeeId,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime,
        ?int $excludeId = null,
    ): array {
        $qb = $this->createQueryBuilder('o')
            ->where('o.employeeId = :employeeId')
            ->andWhere(
                '(o.startTime < :endTime AND o.endTime > :startTime)'
            )
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime)
            ->setParameter('statuses', [
                ApplicationStatus::PENDING->value,
                ApplicationStatus::APPROVED->value,
            ])
        ;

        if (null !== $excludeId) {
            $qb->andWhere('o.id != :excludeId')
                ->setParameter('excludeId', $excludeId)
            ;
        }

        $result = $qb->getQuery()->getResult();

        return array_map(
            fn ($item) => $item instanceof OvertimeApplication ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    public function getTotalOvertimeHours(int $employeeId, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): float
    {
        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.duration)')
            ->where('o.employeeId = :employeeId')
            ->andWhere('o.overtimeDate >= :startDate')
            ->andWhere('o.overtimeDate <= :endDate')
            ->andWhere('o.status = :status')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('status', ApplicationStatus::APPROVED->value)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (float) ($result ?? 0.0);
    }
}
