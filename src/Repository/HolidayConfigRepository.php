<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\AttendanceManageBundle\Entity\HolidayConfig;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<HolidayConfig>
 */
#[AsRepository(entityClass: HolidayConfig::class)]
class HolidayConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HolidayConfig::class);
    }

    public function save(HolidayConfig $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(HolidayConfig $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return HolidayConfig[]
     */
    public function findByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $result = $this->createQueryBuilder('h')
            ->andWhere('h.holidayDate BETWEEN :start AND :end')
            ->andWhere('h.isActive = :active')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('active', true)
            ->orderBy('h.holidayDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn ($item) => $item instanceof HolidayConfig ? $item : throw new \RuntimeException('Invalid type'),
            is_array($result) ? $result : []
        );
    }
}
