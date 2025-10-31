<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\AttendanceManageBundle\Entity\LeaveApplication;
use Tourze\AttendanceManageBundle\Enum\ApplicationStatus;
use Tourze\AttendanceManageBundle\Enum\LeaveType;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<LeaveApplication>
 */
#[AsRepository(entityClass: LeaveApplication::class)]
class LeaveApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LeaveApplication::class);
    }

    public function save(LeaveApplication $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LeaveApplication $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return LeaveApplication[]
     */
    public function findByEmployeeId(int $employeeId): array
    {
        $result = $this->createQueryBuilder('la')
            ->andWhere('la.employeeId = :employeeId')
            ->setParameter('employeeId', $employeeId)
            ->orderBy('la.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof LeaveApplication ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    /**
     * @return LeaveApplication[]
     */
    public function findByEmployeeAndStatus(int $employeeId, string $status): array
    {
        $result = $this->createQueryBuilder('la')
            ->andWhere('la.employeeId = :employeeId')
            ->andWhere('la.status = :status')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('status', $status)
            ->orderBy('la.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof LeaveApplication ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    /**
     * @return LeaveApplication[]
     */
    public function findByStatus(string $status): array
    {
        $result = $this->createQueryBuilder('la')
            ->andWhere('la.status = :status')
            ->setParameter('status', $status)
            ->orderBy('la.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof LeaveApplication ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    /**
     * @return LeaveApplication[]
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $result = $this->createQueryBuilder('la')
            ->andWhere('la.startDate <= :endDate')
            ->andWhere('la.endDate >= :startDate')
            ->andWhere('la.status = :approved')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('approved', ApplicationStatus::APPROVED->value)
            ->orderBy('la.startDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof LeaveApplication ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    /**
     * @return LeaveApplication[]
     */
    public function findOverlappingApplications(
        int $employeeId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        ?int $excludeId = null,
    ): array {
        $qb = $this->createQueryBuilder('la')
            ->andWhere('la.employeeId = :employeeId')
            ->andWhere('la.startDate <= :endDate')
            ->andWhere('la.endDate >= :startDate')
            ->andWhere('la.status IN (:activeStatuses)')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('activeStatuses', [
                ApplicationStatus::PENDING->value,
                ApplicationStatus::APPROVED->value,
            ])
        ;

        if (null !== $excludeId) {
            $qb->andWhere('la.id != :excludeId')
                ->setParameter('excludeId', $excludeId)
            ;
        }

        $result = $qb->getQuery()->getResult();

        return array_map(
            fn ($item) => $item instanceof LeaveApplication ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    /**
     * @return LeaveApplication[]
     */
    public function findByEmployeeAndLeaveType(int $employeeId, LeaveType $leaveType): array
    {
        $result = $this->createQueryBuilder('la')
            ->andWhere('la.employeeId = :employeeId')
            ->andWhere('la.leaveType = :leaveType')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('leaveType', $leaveType)
            ->orderBy('la.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof LeaveApplication ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    public function getTotalApprovedLeaveHours(
        int $employeeId,
        LeaveType $leaveType,
        int $year,
    ): float {
        $startDate = new \DateTimeImmutable("{$year}-01-01");
        $endDate = new \DateTimeImmutable("{$year}-12-31");

        $result = $this->createQueryBuilder('la')
            ->select('SUM(la.duration)')
            ->andWhere('la.employeeId = :employeeId')
            ->andWhere('la.leaveType = :leaveType')
            ->andWhere('la.status = :approved')
            ->andWhere('la.startDate >= :startDate')
            ->andWhere('la.startDate <= :endDate')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('leaveType', $leaveType)
            ->setParameter('approved', ApplicationStatus::APPROVED->value)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (float) ($result ?? 0);
    }

    /**
     * @return LeaveApplication[]
     */
    public function findPendingApplications(): array
    {
        return $this->findByStatus(ApplicationStatus::PENDING->value);
    }

    /**
     * @return LeaveApplication[]
     */
    public function findApplicationsRequiringApproval(int $approverId): array
    {
        $result = $this->createQueryBuilder('la')
            ->andWhere('la.status = :pending')
            ->setParameter('pending', ApplicationStatus::PENDING->value)
            ->orderBy('la.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof LeaveApplication ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    public function countByEmployeeAndYear(int $employeeId, int $year): int
    {
        $startDate = new \DateTimeImmutable("{$year}-01-01");
        $endDate = new \DateTimeImmutable("{$year}-12-31");

        $result = $this->createQueryBuilder('la')
            ->select('COUNT(la.id)')
            ->andWhere('la.employeeId = :employeeId')
            ->andWhere('la.createTime >= :startDate')
            ->andWhere('la.createTime <= :endDate')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($result ?? 0);
    }

    /**
     * @param array<int> $employeeIds
     * @return array<string, array<string, float|int>>
     */
    public function getLeaveStatisticsByYear(int $year, array $employeeIds = []): array
    {
        $startDate = new \DateTimeImmutable("{$year}-01-01");
        $endDate = new \DateTimeImmutable("{$year}-12-31");

        $qb = $this->createQueryBuilder('la')
            ->select('la.leaveType as leaveType, COUNT(la.id) as count, SUM(la.duration) as totalDuration')
            ->andWhere('la.status = :approved')
            ->andWhere('la.startDate >= :startDate')
            ->andWhere('la.startDate <= :endDate')
            ->setParameter('approved', ApplicationStatus::APPROVED->value)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('la.leaveType')
        ;

        if (count($employeeIds) > 0) {
            $qb->andWhere('la.employeeId IN (:employeeIds)')
                ->setParameter('employeeIds', $employeeIds)
            ;
        }

        /** @var array<array{leaveType: LeaveType, count: int, totalDuration: float}> $results */
        $results = $qb->getQuery()->getResult();

        $statistics = [];
        foreach ($results as $result) {
            $statistics[$result['leaveType']->value] = [
                'count' => (int) $result['count'],
                'totalDuration' => (float) $result['totalDuration'],
            ];
        }

        return $statistics;
    }

    /**
     * @return LeaveApplication[]
     */
    public function findExpiringSoon(int $days = 30): array
    {
        $cutoffDate = new \DateTimeImmutable("+{$days} days");

        $result = $this->createQueryBuilder('la')
            ->andWhere('la.status = :approved')
            ->andWhere('la.startDate <= :cutoffDate')
            ->andWhere('la.startDate >= :now')
            ->setParameter('approved', ApplicationStatus::APPROVED->value)
            ->setParameter('cutoffDate', $cutoffDate)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('la.startDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof LeaveApplication ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    /**
     * @return LeaveApplication[]
     */
    public function findCurrentLeaves(): array
    {
        $now = new \DateTimeImmutable();

        $result = $this->createQueryBuilder('la')
            ->andWhere('la.status = :approved')
            ->andWhere('la.startDate <= :now')
            ->andWhere('la.endDate >= :now')
            ->setParameter('approved', ApplicationStatus::APPROVED->value)
            ->setParameter('now', $now)
            ->orderBy('la.employeeId', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof LeaveApplication ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }
}
