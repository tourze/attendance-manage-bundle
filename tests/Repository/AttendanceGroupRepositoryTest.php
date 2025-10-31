<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\OptimisticLockException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AttendanceManageBundle\Entity\AttendanceGroup;
use Tourze\AttendanceManageBundle\Enum\AttendanceGroupType;
use Tourze\AttendanceManageBundle\Repository\AttendanceGroupRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(AttendanceGroupRepository::class)]
#[RunTestsInSeparateProcesses]
class AttendanceGroupRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): AttendanceGroup
    {
        $entity = new AttendanceGroup();
        $entity->setName('Test Group ' . uniqid());
        $entity->setType(AttendanceGroupType::FIXED);
        $entity->setRules([
            'start_time' => '09:00',
            'end_time' => '17:00',
            'break_duration' => 60,
        ]);
        $entity->setMemberIds([1, 2, 3]);

        return $entity;
    }

    protected function getRepository(): AttendanceGroupRepository
    {
        return self::getService(AttendanceGroupRepository::class);
    }

    protected function onSetUp(): void
    {
        // 集成测试设置
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);
    }

    public function testFindByType(): void
    {
        $repository = $this->getRepository();

        // 创建一个测试实体
        $entity = $this->createNewEntity();
        $repository->save($entity, true);

        $results = $repository->findByType('fixed');
        $this->assertIsArray($results);
        $this->assertContainsOnlyInstancesOf(AttendanceGroup::class, $results);
    }

    public function testFindByMember(): void
    {
        $repository = $this->getRepository();

        // 创建一个测试实体
        $entity = $this->createNewEntity();
        $repository->save($entity, true);

        // 测试能找到成员
        $result = $repository->findByMember(1);
        $this->assertInstanceOf(AttendanceGroup::class, $result);

        // 测试找不到的成员
        $result = $repository->findByMember(999999);
        $this->assertNull($result);
    }

    public function testFindByEmployeeIds(): void
    {
        $repository = $this->getRepository();

        // 创建一个测试实体
        $entity = $this->createNewEntity();
        $repository->save($entity, true);

        $results = $repository->findByEmployeeIds([1, 2, 3]);
        $this->assertIsArray($results);
        $this->assertContainsOnlyInstancesOf(AttendanceGroup::class, $results);
    }

    public function testFindWithMembersCount(): void
    {
        $repository = $this->getRepository();

        $results = $repository->findWithMembersCount();
        $this->assertIsArray($results);
        $this->assertContainsOnlyInstancesOf(AttendanceGroup::class, $results);
    }

    public function testFindGroupsWithoutMembers(): void
    {
        $repository = $this->getRepository();

        // 创建一个没有成员的组
        $entityWithoutMembers = new AttendanceGroup();
        $entityWithoutMembers->setName('Empty Group ' . uniqid());
        $entityWithoutMembers->setType(AttendanceGroupType::FLEXIBLE);
        $entityWithoutMembers->setRules([]);
        $entityWithoutMembers->setMemberIds([]);
        $repository->save($entityWithoutMembers, true);

        $results = $repository->findGroupsWithoutMembers();
        $this->assertIsArray($results);
        $this->assertContainsOnlyInstancesOf(AttendanceGroup::class, $results);
    }

    public function testCountByType(): void
    {
        $repository = $this->getRepository();

        // 创建一个测试实体
        $entity = $this->createNewEntity();
        $repository->save($entity, true);

        $count = $repository->countByType('fixed');
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testFindActive(): void
    {
        $repository = $this->getRepository();

        // 创建一个测试实体
        $entity = $this->createNewEntity();
        $repository->save($entity, true);

        $results = $repository->findActive();
        $this->assertIsArray($results);
        $this->assertContainsOnlyInstancesOf(AttendanceGroup::class, $results);
    }

    public function testFindWithOptimisticLockWhenVersionMismatchesShouldThrowExceptionOnFlush(): void
    {
        $repository = $this->getRepository();
        $em = self::getEntityManager();

        // 1. 创建并保存一个实体
        $entity = $this->createNewEntity();
        $repository->save($entity, true);
        $id = $entity->getId();
        $this->assertNotNull($id);

        // 2. 重新加载实体（模拟第一个事务）
        $em->clear();
        $loadedEntity = $repository->find($id);
        $this->assertNotNull($loadedEntity);
        $originalVersion = $loadedEntity->getVersion();

        // 3. 使用直接DBAL更新版本号（模拟外部并发更新）
        $connection = $em->getConnection();
        $connection->executeStatement(
            'UPDATE attendance_groups SET version = version + 1, update_time = ? WHERE id = ?',
            [new \DateTimeImmutable(), $id],
            ['datetime_immutable', 'integer']
        );

        // 4. 修改第一个事务中的实体
        $loadedEntity->setName('Updated Name ' . uniqid());

        // 5. 预期flush会因为版本冲突抛出OptimisticLockException
        $this->expectException(OptimisticLockException::class);
        $em->flush();
    }

    public function testFindWithPessimisticWriteLockShouldReturnEntityAndLockRow(): void
    {
        $repository = $this->getRepository();
        $em = self::getEntityManager();

        // 1. 创建并保存一个实体
        $entity = $this->createNewEntity();
        $repository->save($entity, true);
        $id = $entity->getId();
        $this->assertNotNull($id);

        // 2. 在事务中使用悲观锁查找实体
        $em->beginTransaction();
        try {
            $lockedEntity = $repository->find($id, LockMode::PESSIMISTIC_WRITE);

            // 3. 断言返回的是正确的实体
            $this->assertInstanceOf(AttendanceGroup::class, $lockedEntity);
            $this->assertSame($id, $lockedEntity->getId());

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }
}
