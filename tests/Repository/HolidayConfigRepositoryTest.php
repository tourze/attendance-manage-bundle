<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AttendanceManageBundle\Entity\HolidayConfig;
use Tourze\AttendanceManageBundle\Exception\AttendanceException;
use Tourze\AttendanceManageBundle\Repository\HolidayConfigRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(HolidayConfigRepository::class)]
#[RunTestsInSeparateProcesses]
class HolidayConfigRepositoryTest extends AbstractRepositoryTestCase
{
    protected function getRepositoryClass(): string
    {
        return HolidayConfigRepository::class;
    }

    protected function getEntityClass(): string
    {
        return HolidayConfig::class;
    }

    protected function getRepository(): HolidayConfigRepository
    {
        $repository = self::getEntityManager()->getRepository(HolidayConfig::class);
        if (!$repository instanceof HolidayConfigRepository) {
            throw new AttendanceException('Expected HolidayConfigRepository instance');
        }

        return $repository;
    }

    protected function createNewEntity(): HolidayConfig
    {
        $config = new HolidayConfig();
        $config->setName('测试假日');
        $config->setHolidayDate(new \DateTimeImmutable('2024-01-01'));
        $config->setType('national');

        return $config;
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

    public function testFindByDateRange(): void
    {
        $repository = $this->createMock(HolidayConfigRepository::class);
        $repository->method('findByDateRange')->willReturn([]);

        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $this->assertEquals([], $repository->findByDateRange($startDate, $endDate));
    }
}
