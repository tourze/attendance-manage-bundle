<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AttendanceManageBundle\Entity\AttendanceGroup;
use Tourze\AttendanceManageBundle\Enum\AttendanceGroupType;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(AttendanceGroup::class)]
class AttendanceGroupTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $entity = new AttendanceGroup();
        $entity->setName('Test Group');
        $entity->setType(AttendanceGroupType::FIXED);
        $entity->setRules(['rule1' => 'value1']);
        $entity->setMemberIds([1, 2, 3]);

        return $entity;
    }

    public static function propertiesProvider(): iterable
    {
        return [
            'name' => ['name', 'test_value'],
            'type' => ['type', AttendanceGroupType::FLEXIBLE],
            'rules' => ['rules', ['key' => 'value']],
        ];
    }

    public function testConstructor(): void
    {
        $name = 'Test Group';
        $type = AttendanceGroupType::FIXED;
        $rules = ['rule1' => 'value1'];
        $memberIds = [1, 2, 3];

        $group = new AttendanceGroup();
        $group->setName($name);
        $group->setType($type);
        $group->setRules($rules);
        $group->setMemberIds($memberIds);

        $this->assertEquals($name, $group->getName());
        $this->assertEquals($type, $group->getType());
        $this->assertEquals($rules, $group->getRules());
        $this->assertEquals($memberIds, $group->getMemberIds());
        $this->assertTrue($group->isActive());
        $this->assertInstanceOf(\DateTimeImmutable::class, $group->getCreateTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $group->getUpdateTime());
    }

    public function testAddMember(): void
    {
        $group = new AttendanceGroup();
        $group->setName('Test Group');
        $group->setType(AttendanceGroupType::FIXED);

        $group->addMember(1);
        $this->assertTrue($group->hasMember(1));
        $this->assertEquals([1], $group->getMemberIds());

        $group->addMember(1);
        $this->assertEquals([1], $group->getMemberIds());

        $group->addMember(2);
        $this->assertTrue($group->hasMember(2));
        $this->assertEquals([1, 2], $group->getMemberIds());
    }

    public function testRemoveMember(): void
    {
        $group = new AttendanceGroup();
        $group->setName('Test Group');
        $group->setType(AttendanceGroupType::FIXED);
        $group->setMemberIds([1, 2, 3]);

        $group->removeMember(2);
        $this->assertFalse($group->hasMember(2));
        $this->assertEquals([1, 3], $group->getMemberIds());

        $group->removeMember(5);
        $this->assertEquals([1, 3], $group->getMemberIds());
    }

    public function testTypeCheckers(): void
    {
        $fixedGroup = new AttendanceGroup();
        $fixedGroup->setName('Fixed Group');
        $fixedGroup->setType(AttendanceGroupType::FIXED);
        $this->assertTrue($fixedGroup->isFixedTime());
        $this->assertFalse($fixedGroup->isFlexibleTime());
        $this->assertFalse($fixedGroup->isShiftWork());

        $flexibleGroup = new AttendanceGroup();
        $flexibleGroup->setName('Flexible Group');
        $flexibleGroup->setType(AttendanceGroupType::FLEXIBLE);
        $this->assertFalse($flexibleGroup->isFixedTime());
        $this->assertTrue($flexibleGroup->isFlexibleTime());
        $this->assertFalse($flexibleGroup->isShiftWork());

        $shiftGroup = new AttendanceGroup();
        $shiftGroup->setName('Shift Group');
        $shiftGroup->setType(AttendanceGroupType::SHIFT);
        $this->assertFalse($shiftGroup->isFixedTime());
        $this->assertFalse($shiftGroup->isFlexibleTime());
        $this->assertTrue($shiftGroup->isShiftWork());
    }

    public function testSetters(): void
    {
        $group = new AttendanceGroup();
        $group->setName('Test Group');
        $group->setType(AttendanceGroupType::FIXED);
        $originalUpdatedAt = $group->getUpdateTime();

        sleep(1);

        $group->setName('Updated Group');
        $this->assertEquals('Updated Group', $group->getName());
        $this->assertGreaterThan($originalUpdatedAt, $group->getUpdateTime());

        $group->setType(AttendanceGroupType::FLEXIBLE);
        $this->assertEquals(AttendanceGroupType::FLEXIBLE, $group->getType());

        $rules = ['new_rule' => 'new_value'];
        $group->setRules($rules);
        $this->assertEquals($rules, $group->getRules());

        $group->setActive(false);
        $this->assertFalse($group->isActive());
    }
}
