<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
class AttendanceGroupRepositoryMethodTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): AttendanceGroup
    {
        $group = new AttendanceGroup();
        $group->setName('Test Group ' . uniqid());
        $group->setType(AttendanceGroupType::FIXED);
        $group->setRules(['rule1' => 'value1']);

        return $group;
    }

    protected function getRepository(): AttendanceGroupRepository
    {
        $repository = self::getContainer()->get(AttendanceGroupRepository::class);
        self::assertInstanceOf(AttendanceGroupRepository::class, $repository);

        return $repository;
    }

    private function getAttendanceGroupRepository(): AttendanceGroupRepository
    {
        $repository = self::getContainer()->get(AttendanceGroupRepository::class);
        self::assertInstanceOf(AttendanceGroupRepository::class, $repository);

        return $repository;
    }

    protected function onSetUp(): void
    {
        // No setup needed for unit tests
    }

    public function testHasAllRequiredMethods(): void
    {
        $repository = $this->getAttendanceGroupRepository();
        $entity = $this->createNewEntity();

        // Test save method
        $repository->save($entity, true);
        $this->assertNotNull($entity->getId());

        // Test remove method
        $entityId = $entity->getId();
        $repository->remove($entity, true);
        $foundEntity = $repository->find($entityId);
        $this->assertNull($foundEntity);
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $repository = $this->getAttendanceGroupRepository();
        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);
    }

    public function testCountByType(): void
    {
        $repository = $this->getAttendanceGroupRepository();
        $count = $repository->countByType('fixed');

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testFindActive(): void
    {
        $repository = $this->getAttendanceGroupRepository();
        $result = $repository->findActive();

        $this->assertIsArray($result);
    }

    public function testFindByEmployeeId(): void
    {
        $repository = $this->getAttendanceGroupRepository();
        $result = $repository->findByEmployeeId(999999);

        $this->assertNull($result);
    }

    public function testFindByEmployeeIds(): void
    {
        $repository = $this->getAttendanceGroupRepository();
        $result = $repository->findByEmployeeIds([999999, 999998]);

        $this->assertIsArray($result);
    }

    public function testFindByMember(): void
    {
        $repository = $this->getAttendanceGroupRepository();
        $result = $repository->findByMember(999999);

        $this->assertNull($result);
    }

    public function testFindByType(): void
    {
        $repository = $this->getAttendanceGroupRepository();
        $result = $repository->findByType('fixed');

        $this->assertIsArray($result);
    }

    public function testFindGroupsWithoutMembers(): void
    {
        $repository = $this->getAttendanceGroupRepository();
        $result = $repository->findGroupsWithoutMembers();

        $this->assertIsArray($result);
    }

    public function testFindWithMembersCount(): void
    {
        $repository = $this->getAttendanceGroupRepository();
        $result = $repository->findWithMembersCount();

        $this->assertIsArray($result);
    }

    public function testRemove(): void
    {
        $repository = $this->getAttendanceGroupRepository();
        $entity = $this->createNewEntity();

        // First save the entity
        $repository->save($entity, true);
        $entityId = $entity->getId();
        $this->assertNotNull($entityId);

        // Then remove it
        $repository->remove($entity, true);

        // Verify it's removed
        $foundEntity = $repository->find($entityId);
        $this->assertNull($foundEntity);
    }
}
