<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AttendanceManageBundle\Entity\WorkShift;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(WorkShift::class)]
class WorkShiftTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $workShift = new WorkShift();
        $workShift->setGroupId(1);
        $workShift->setName('标准班次');
        $workShift->setStartTime(new \DateTimeImmutable('09:00:00'));
        $workShift->setEndTime(new \DateTimeImmutable('18:00:00'));
        $workShift->setBreakTimes([]);
        $workShift->setCrossDay(false);

        return $workShift;
    }

    public static function propertiesProvider(): iterable
    {
        return [
            'groupId' => ['groupId', 123],
            'name' => ['name', 'test_value'],
            'breakTimes' => ['breakTimes', ['key' => 'value']],
            'crossDay' => ['crossDay', true],
        ];
    }

    public function testConstructor(): void
    {
        $groupId = 1;
        $name = '标准班次';
        $startTime = new \DateTimeImmutable('09:00:00');
        $endTime = new \DateTimeImmutable('18:00:00');
        $flexibleMinutes = 15;
        $breakTimes = [
            ['start' => '12:00', 'end' => '13:00'],
            ['start' => '15:00', 'end' => '15:15'],
        ];
        $crossDay = false;

        $workShift = new WorkShift();
        $workShift->setGroupId($groupId);
        $workShift->setName($name);
        $workShift->setStartTime($startTime);
        $workShift->setEndTime($endTime);
        $workShift->setFlexibleMinutes($flexibleMinutes);
        $workShift->setBreakTimes($breakTimes);
        $workShift->setCrossDay($crossDay);

        $this->assertEquals($groupId, $workShift->getGroupId());
        $this->assertEquals($name, $workShift->getName());
        $this->assertEquals($startTime, $workShift->getStartTime());
        $this->assertEquals($endTime, $workShift->getEndTime());
        $this->assertEquals($flexibleMinutes, $workShift->getFlexibleMinutes());
        $this->assertEquals($breakTimes, $workShift->getBreakTimes());
        $this->assertEquals($crossDay, $workShift->isCrossDay());
        $this->assertTrue($workShift->isActive());
        $this->assertInstanceOf(\DateTimeImmutable::class, $workShift->getCreateTime());
    }

    public function testConstructorWithDefaults(): void
    {
        $groupId = 1;
        $name = '标准班次';
        $startTime = new \DateTimeImmutable('09:00:00');
        $endTime = new \DateTimeImmutable('18:00:00');

        $workShift = new WorkShift();
        $workShift->setGroupId($groupId);
        $workShift->setName($name);
        $workShift->setStartTime($startTime);
        $workShift->setEndTime($endTime);

        $this->assertNull($workShift->getFlexibleMinutes());
        $this->assertEquals([], $workShift->getBreakTimes());
        $this->assertFalse($workShift->isCrossDay());
        $this->assertTrue($workShift->isActive());
    }

    public function testSetters(): void
    {
        $workShift = $this->createWorkShift();

        $workShift->setGroupId(2);
        $this->assertEquals(2, $workShift->getGroupId());

        $workShift->setName('新班次');
        $this->assertEquals('新班次', $workShift->getName());

        $newStartTime = new \DateTimeImmutable('08:00:00');
        $workShift->setStartTime($newStartTime);
        $this->assertEquals($newStartTime, $workShift->getStartTime());

        $newEndTime = new \DateTimeImmutable('17:00:00');
        $workShift->setEndTime($newEndTime);
        $this->assertEquals($newEndTime, $workShift->getEndTime());

        $workShift->setFlexibleMinutes(30);
        $this->assertEquals(30, $workShift->getFlexibleMinutes());

        $newBreakTimes = [['start' => '12:30', 'end' => '13:30']];
        $workShift->setBreakTimes($newBreakTimes);
        $this->assertEquals($newBreakTimes, $workShift->getBreakTimes());

        $workShift->setCrossDay(true);
        $this->assertTrue($workShift->isCrossDay());

        $workShift->setActive(false);
        $this->assertFalse($workShift->isActive());
    }

    public function testAddBreakTime(): void
    {
        $workShift = new WorkShift();
        $workShift->setGroupId(1);
        $workShift->setName('标准班次');
        $workShift->setStartTime(new \DateTimeImmutable('09:00:00'));
        $workShift->setEndTime(new \DateTimeImmutable('18:00:00'));

        $workShift->addBreakTime('12:00', '13:00');

        $expected = [['start' => '12:00', 'end' => '13:00']];
        $this->assertEquals($expected, $workShift->getBreakTimes());

        $workShift->addBreakTime('15:00', '15:15');
        $expected = [
            ['start' => '12:00', 'end' => '13:00'],
            ['start' => '15:00', 'end' => '15:15'],
        ];
        $this->assertEquals($expected, $workShift->getBreakTimes());
    }

    public function testGetTotalBreakMinutes(): void
    {
        $workShift = new WorkShift();
        $workShift->setGroupId(1);
        $workShift->setName('标准班次');
        $workShift->setStartTime(new \DateTimeImmutable('09:00:00'));
        $workShift->setEndTime(new \DateTimeImmutable('18:00:00'));
        $workShift->setFlexibleMinutes(null);
        $workShift->setBreakTimes([
            ['start' => '12:00', 'end' => '13:00'],
            ['start' => '15:00', 'end' => '15:15'],
        ]);
        $workShift->setCrossDay(false);

        $this->assertEquals(75, $workShift->getTotalBreakMinutes());
    }

    public function testGetTotalBreakMinutesEmpty(): void
    {
        $workShift = $this->createWorkShift();
        $this->assertEquals(0, $workShift->getTotalBreakMinutes());
    }

    public function testGetWorkDurationMinutesNormalShift(): void
    {
        $workShift = new WorkShift();
        $workShift->setGroupId(1);
        $workShift->setName('标准班次');
        $workShift->setStartTime(new \DateTimeImmutable('09:00:00'));
        $workShift->setEndTime(new \DateTimeImmutable('18:00:00'));
        $workShift->setFlexibleMinutes(null);
        $workShift->setBreakTimes([
            ['start' => '12:00', 'end' => '13:00'],
        ]);
        $workShift->setCrossDay(false);

        $expected = 9 * 60 - 60;
        $this->assertEquals($expected, $workShift->getWorkDurationMinutes());
    }

    public function testGetWorkDurationMinutesCrossDayShift(): void
    {
        $workShift = new WorkShift();
        $workShift->setGroupId(1);
        $workShift->setName('夜班');
        $workShift->setStartTime(new \DateTimeImmutable('22:00:00'));
        $workShift->setEndTime(new \DateTimeImmutable('06:00:00'));
        $workShift->setFlexibleMinutes(null);
        $workShift->setBreakTimes([]);
        $workShift->setCrossDay(true);

        $expected = (2 * 60) + (6 * 60);
        $this->assertEquals($expected, $workShift->getWorkDurationMinutes());
    }

    public function testIsWithinFlexibleRange(): void
    {
        $workShift = new WorkShift();
        $workShift->setGroupId(1);
        $workShift->setName('弹性班次');
        $workShift->setStartTime(new \DateTimeImmutable('09:00:00'));
        $workShift->setEndTime(new \DateTimeImmutable('18:00:00'));
        $workShift->setFlexibleMinutes(15);

        $this->assertTrue($workShift->isWithinFlexibleRange(new \DateTimeImmutable('08:50:00')));
        $this->assertTrue($workShift->isWithinFlexibleRange(new \DateTimeImmutable('09:00:00')));
        $this->assertTrue($workShift->isWithinFlexibleRange(new \DateTimeImmutable('09:10:00')));
        $this->assertFalse($workShift->isWithinFlexibleRange(new \DateTimeImmutable('08:40:00')));
        $this->assertFalse($workShift->isWithinFlexibleRange(new \DateTimeImmutable('09:20:00')));
    }

    public function testIsWithinFlexibleRangeNoFlexibility(): void
    {
        $workShift = $this->createWorkShift();
        $this->assertFalse($workShift->isWithinFlexibleRange(new \DateTimeImmutable('09:00:00')));
    }

    public function testToString(): void
    {
        $workShift = new WorkShift();
        $workShift->setGroupId(1);
        $workShift->setName('标准班次');
        $workShift->setStartTime(new \DateTimeImmutable('09:00:00'));
        $workShift->setEndTime(new \DateTimeImmutable('18:00:00'));

        $expected = '标准班次 (09:00-18:00)';
        $this->assertEquals($expected, (string) $workShift);
    }

    public function testToStringCrossDay(): void
    {
        $workShift = new WorkShift();
        $workShift->setGroupId(1);
        $workShift->setName('夜班');
        $workShift->setStartTime(new \DateTimeImmutable('22:00:00'));
        $workShift->setEndTime(new \DateTimeImmutable('06:00:00'));
        $workShift->setFlexibleMinutes(null);
        $workShift->setBreakTimes([]);
        $workShift->setCrossDay(true);

        $expected = '夜班 (22:00-06:00)';
        $this->assertEquals($expected, (string) $workShift);
    }

    public function testGetWorkDurationMinutesWithInvalidBreakTime(): void
    {
        $workShift = new WorkShift();
        $workShift->setGroupId(1);
        $workShift->setName('标准班次');
        $workShift->setStartTime(new \DateTimeImmutable('09:00:00'));
        $workShift->setEndTime(new \DateTimeImmutable('18:00:00'));
        $workShift->setFlexibleMinutes(null);
        $workShift->setBreakTimes([
            ['start' => '12:00', 'end' => '13:00'],
            ['start' => 'invalid', 'end' => 'time'],
        ]);
        $workShift->setCrossDay(false);

        $expected = 9 * 60 - 60;
        $this->assertEquals($expected, $workShift->getWorkDurationMinutes());
    }

    private function createWorkShift(): WorkShift
    {
        $workShift = new WorkShift();
        $workShift->setGroupId(1);
        $workShift->setName('标准班次');
        $workShift->setStartTime(new \DateTimeImmutable('09:00:00'));
        $workShift->setEndTime(new \DateTimeImmutable('18:00:00'));

        return $workShift;
    }
}
