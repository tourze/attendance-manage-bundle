<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AttendanceManageBundle\Entity\HolidayConfig;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(HolidayConfig::class)]
class HolidayConfigTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $entity = new HolidayConfig();
        $entity->setName('元旦');
        $entity->setHolidayDate(new \DateTimeImmutable('2024-01-01'));
        $entity->setType(HolidayConfig::TYPE_NATIONAL);
        $entity->setDescription('新年第一天');

        return $entity;
    }

    public static function propertiesProvider(): iterable
    {
        return [
            'name' => ['name', 'test_value'],
            'type' => ['type', HolidayConfig::TYPE_COMPANY],
        ];
    }

    public function testConstruct(): void
    {
        $name = '元旦';
        $date = new \DateTimeImmutable('2024-01-01');
        $type = HolidayConfig::TYPE_NATIONAL;
        $description = '新年第一天';

        $holiday = new HolidayConfig();
        $holiday->setName($name);
        $holiday->setHolidayDate($date);
        $holiday->setType($type);
        $holiday->setDescription($description);
        $holiday->setApplicableDepartments(null);

        $this->assertSame($name, $holiday->getName());
        $this->assertSame($date, $holiday->getHolidayDate());
        $this->assertSame($type, $holiday->getType());
        $this->assertSame($description, $holiday->getDescription());
        $this->assertTrue($holiday->isPaid());
        $this->assertTrue($holiday->isMandatory());
        $this->assertTrue($holiday->isActive());
        $this->assertNull($holiday->getApplicableDepartments());
    }
}
