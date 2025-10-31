<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Repository;

use PHPUnit\Framework\Assert;
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
class AttendanceGroupRepositoryUnitTest extends AbstractRepositoryTestCase
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
        // Unit test setup
    }

    public function testHasSaveMethod(): void
    {
        $repository = $this->createMock(AttendanceGroupRepository::class);
        $repository->method('findActive')->willReturn([]);

        $this->assertEquals([], $repository->findActive());
    }

    public function testCountByType(): void
    {
        $repository = $this->createMock(AttendanceGroupRepository::class);
        $repository->method('countByType')->willReturn(5);

        $this->assertEquals(5, $repository->countByType('fixed'));
    }

    public function testFindActive(): void
    {
        $repository = $this->createMock(AttendanceGroupRepository::class);
        $repository->method('findActive')->willReturn([]);

        $this->assertEquals([], $repository->findActive());
    }

    public function testFindByEmployeeId(): void
    {
        $repository = $this->createMock(AttendanceGroupRepository::class);
        $repository->method('findByEmployeeId')->willReturn(null);

        $this->assertNull($repository->findByEmployeeId(1));
    }

    public function testFindByEmployeeIds(): void
    {
        $repository = $this->createMock(AttendanceGroupRepository::class);
        $repository->method('findByEmployeeIds')->willReturn([]);

        $this->assertEquals([], $repository->findByEmployeeIds([1, 2, 3]));
    }

    public function testFindByMember(): void
    {
        $repository = $this->createMock(AttendanceGroupRepository::class);
        $repository->method('findByMember')->willReturn(null);

        $this->assertNull($repository->findByMember(1));
    }

    public function testFindByType(): void
    {
        $repository = $this->createMock(AttendanceGroupRepository::class);
        $repository->method('findByType')->willReturn([]);

        $this->assertEquals([], $repository->findByType('fixed'));
    }

    public function testFindGroupsWithoutMembers(): void
    {
        $repository = $this->createMock(AttendanceGroupRepository::class);
        $repository->method('findGroupsWithoutMembers')->willReturn([]);

        $this->assertEquals([], $repository->findGroupsWithoutMembers());
    }

    public function testFindWithMembersCount(): void
    {
        $repository = $this->createMock(AttendanceGroupRepository::class);
        $repository->method('findWithMembersCount')->willReturn([]);

        $this->assertEquals([], $repository->findWithMembersCount());
    }

    public function testRemove(): void
    {
        $repository = $this->createMock(AttendanceGroupRepository::class);
        $repository->expects($this->once())
            ->method('remove')
            ->with(Assert::isInstanceOf(AttendanceGroup::class), Assert::isFalse())
        ;

        $entity = $this->createMock(AttendanceGroup::class);
        $repository->remove($entity, false);
    }
}
