<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\AttendanceManageBundle\Entity\AttendanceGroup;
use Tourze\AttendanceManageBundle\Enum\AttendanceGroupType;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(AttendanceGroup::class)]
class AttendanceGroupValidationTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $entity = new AttendanceGroup();
        $entity->setName('Test Group');
        $entity->setType(AttendanceGroupType::FIXED);
        $entity->setRules(['working_hours' => 8]);
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

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;
    }

    public function testValidAttendanceGroup(): void
    {
        $group = new AttendanceGroup();
        $group->setName('Research Team');
        $group->setType(AttendanceGroupType::FIXED);
        $group->setRules(['working_hours' => 8]);
        $group->setMemberIds([1, 2, 3]);

        $violations = $this->validator->validate($group);
        $this->assertCount(0, $violations);
    }

    public function testNameTooLong(): void
    {
        $longName = str_repeat('a', 101); // 超过100字符
        $group = new AttendanceGroup();
        $group->setName($longName);
        $group->setType(AttendanceGroupType::FIXED);
        $group->setRules(['working_hours' => 8]);
        $group->setMemberIds([1, 2, 3]);

        $violations = $this->validator->validate($group);
        $this->assertGreaterThan(0, $violations->count());

        $hasLengthViolation = false;
        foreach ($violations as $violation) {
            $message = (string) $violation->getMessage();
            if (str_contains($message, '100')) {
                $hasLengthViolation = true;
                break;
            }
        }
        $this->assertTrue($hasLengthViolation, 'Should have length constraint violation');
    }

    public function testTypeTooLong(): void
    {
        // 使用枚举时，类型长度由枚举值本身控制，所以这个测试不再适用
        // 枚举值已经在数据库层面限制了长度
        $group = new AttendanceGroup();
        $group->setName('Valid Name');
        $group->setType(AttendanceGroupType::FIXED);
        $group->setRules(['working_hours' => 8]);
        $group->setMemberIds([1, 2, 3]);

        $violations = $this->validator->validate($group);
        $this->assertEquals(0, $violations->count(), '枚举类型应该总是有效的');
    }

    public function testEmptyName(): void
    {
        $group = new AttendanceGroup();
        $group->setName('');
        $group->setType(AttendanceGroupType::FIXED);
        $group->setRules(['working_hours' => 8]);
        $group->setMemberIds([1, 2, 3]);

        $violations = $this->validator->validate($group);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testValidTypeEnum(): void
    {
        // 由于使用了枚举，类型总是有效的，不存在"空类型"的情况
        $group = new AttendanceGroup();
        $group->setName('Valid Name');
        $group->setType(AttendanceGroupType::FLEXIBLE);
        $group->setRules(['working_hours' => 8]);
        $group->setMemberIds([1, 2, 3]);

        $violations = $this->validator->validate($group);
        $this->assertEquals(0, $violations->count(), '所有枚举值都应该是有效的');
    }
}
