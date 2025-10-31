<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\AttendanceManageBundle\Entity\AttendanceRecord;
use Tourze\AttendanceManageBundle\Enum\AttendanceStatus;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<AttendanceRecord>
 */
#[AsRepository(entityClass: AttendanceRecord::class)]
class AttendanceRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AttendanceRecord::class);
    }

    public function save(AttendanceRecord $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AttendanceRecord $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByEmployeeAndDate(int $employeeId, \DateTimeInterface $date): ?AttendanceRecord
    {
        $result = $this->createQueryBuilder('ar')
            ->andWhere('ar.employeeId = :employeeId')
            ->andWhere('ar.workDate = :date')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (null === $result) {
            return null;
        }

        if (!$result instanceof AttendanceRecord) {
            throw new \RuntimeException('Invalid type');
        }

        return $result;
    }

    /**
     * @return array<AttendanceRecord>
     */
    public function findByEmployeeAndDateRange(
        int $employeeId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
    ): array {
        $result = $this->createQueryBuilder('ar')
            ->andWhere('ar.employeeId = :employeeId')
            ->andWhere('ar.workDate >= :startDate')
            ->andWhere('ar.workDate <= :endDate')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('startDate', $startDate->format('Y-m-d'))
            ->setParameter('endDate', $endDate->format('Y-m-d'))
            ->orderBy('ar.workDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof AttendanceRecord ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    /**
     * @return AttendanceRecord[]
     */
    public function findByEmployeeAndMonth(int $employeeId, int $year, int $month): array
    {
        $startDate = new \DateTimeImmutable("{$year}-{$month}-01");
        $endDate = $startDate->modify('last day of this month');

        return $this->findByEmployeeAndDateRange($employeeId, $startDate, $endDate);
    }

    /**
     * @param array<int> $employeeIds
     * @return array<AttendanceRecord>
     */
    public function findByDateAndEmployees(\DateTimeInterface $date, array $employeeIds): array
    {
        $result = $this->createQueryBuilder('ar')
            ->andWhere('ar.workDate = :date')
            ->andWhere('ar.employeeId IN (:employeeIds)')
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('employeeIds', $employeeIds)
            ->orderBy('ar.employeeId', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof AttendanceRecord ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    /**
     * @return array<AttendanceRecord>
     */
    public function findByStatus(AttendanceStatus $status, \DateTimeInterface $date): array
    {
        $result = $this->createQueryBuilder('ar')
            ->andWhere('ar.status = :status')
            ->andWhere('ar.workDate = :date')
            ->setParameter('status', $status)
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('ar.employeeId', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof AttendanceRecord ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    /**
     * @return array<AttendanceRecord>
     */
    public function findAbnormalRecords(\DateTimeInterface $date): array
    {
        $result = $this->createQueryBuilder('ar')
            ->andWhere('ar.workDate = :date')
            ->andWhere('ar.abnormalReason IS NOT NULL')
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('ar.employeeId', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof AttendanceRecord ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    /**
     * @return array<AttendanceRecord>
     */
    public function findIncompleteRecords(\DateTimeInterface $date): array
    {
        $result = $this->createQueryBuilder('ar')
            ->andWhere('ar.workDate = :date')
            ->andWhere('ar.checkInTime IS NULL OR ar.checkOutTime IS NULL')
            ->andWhere('ar.status != :leave')
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('leave', AttendanceStatus::LEAVE)
            ->orderBy('ar.employeeId', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof AttendanceRecord ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    public function countByEmployeeAndStatus(
        int $employeeId,
        AttendanceStatus $status,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
    ): int {
        $result = $this->createQueryBuilder('ar')
            ->select('COUNT(ar.id)')
            ->andWhere('ar.employeeId = :employeeId')
            ->andWhere('ar.status = :status')
            ->andWhere('ar.workDate >= :startDate')
            ->andWhere('ar.workDate <= :endDate')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('status', $status)
            ->setParameter('startDate', $startDate->format('Y-m-d'))
            ->setParameter('endDate', $endDate->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($result ?? 0);
    }

    /**
     * @param array<int> $employeeIds
     * @return array<string, int>
     */
    public function getStatusStatisticsByDateRange(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        array $employeeIds = [],
    ): array {
        $qb = $this->createQueryBuilder('ar')
            ->select('ar.status as status, COUNT(ar.id) as count')
            ->andWhere('ar.workDate >= :startDate')
            ->andWhere('ar.workDate <= :endDate')
            ->setParameter('startDate', $startDate->format('Y-m-d'))
            ->setParameter('endDate', $endDate->format('Y-m-d'))
            ->groupBy('ar.status')
        ;

        if (count($employeeIds) > 0) {
            $qb->andWhere('ar.employeeId IN (:employeeIds)')
                ->setParameter('employeeIds', $employeeIds)
            ;
        }

        /** @var array<array{status: AttendanceStatus, count: int}> $results */
        $results = $qb->getQuery()->getResult();

        $statistics = [];
        foreach ($results as $result) {
            if (is_array($result)
                && isset($result['status'], $result['count'])
                && $result['status'] instanceof AttendanceStatus
                && is_numeric($result['count'])
            ) {
                $statistics[$result['status']->value] = (int) $result['count'];
            }
        }

        return $statistics;
    }

    /**
     * @return array<AttendanceRecord>
     */
    public function findRecordsNeedingAttention(\DateTimeInterface $date): array
    {
        $result = $this->createQueryBuilder('ar')
            ->andWhere('ar.workDate = :date')
            ->andWhere(
                'ar.status IN (:abnormalStatuses) OR ar.abnormalReason IS NOT NULL'
            )
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('abnormalStatuses', [
                AttendanceStatus::LATE,
                AttendanceStatus::EARLY,
                AttendanceStatus::ABSENT,
            ])
            ->orderBy('ar.employeeId', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof AttendanceRecord ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }

    public function getTotalWorkMinutesByEmployee(
        int $employeeId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
    ): int {
        $records = $this->findByEmployeeAndDateRange($employeeId, $startDate, $endDate);

        $totalMinutes = 0;
        foreach ($records as $record) {
            $minutes = $record->getWorkDurationMinutes();
            if (null !== $minutes) {
                $totalMinutes += $minutes;
            }
        }

        return $totalMinutes;
    }

    public function findRecentRecord(int $employeeId, \DateTimeInterface $since): ?AttendanceRecord
    {
        $result = $this->createQueryBuilder('ar')
            ->andWhere('ar.employeeId = :employeeId')
            ->andWhere('ar.checkInTime >= :since')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('since', $since)
            ->orderBy('ar.checkInTime', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (null === $result) {
            return null;
        }

        if (!$result instanceof AttendanceRecord) {
            throw new \RuntimeException('Invalid type');
        }

        return $result;
    }
}
