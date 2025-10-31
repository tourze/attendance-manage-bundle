<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Service;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\AttendanceManageBundle\Entity\HolidayConfig;
use Tourze\AttendanceManageBundle\Exception\AttendanceException;
use Tourze\AttendanceManageBundle\Repository\HolidayConfigRepository;
use Tourze\AttendanceManageBundle\Service\HolidayService;

/**
 * @internal
 */
#[CoversClass(HolidayService::class)]
class HolidayServiceTest extends TestCase
{
    private HolidayService $service;

    private MockObject $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(HolidayConfigRepository::class);
        $this->service = new HolidayService($this->repository);
    }

    public function testCreateHolidayConfig(): void
    {
        $name = '春节';
        $date = new \DateTimeImmutable('2025-01-29');
        $type = 'national';

        $this->repository->expects($this->once())
            ->method('save')
            ->with(Assert::isInstanceOf(HolidayConfig::class), true)
        ;

        $config = $this->service->createHoliday($name, $date, $type);

        $this->assertInstanceOf(HolidayConfig::class, $config);
        $this->assertEquals('春节', $config->getName());
        $this->assertEquals('national', $config->getType());
    }

    public function testCreateHolidayConfigInvalidData(): void
    {
        // Test with invalid type should throw exception during construction
        $this->expectException(AttendanceException::class);

        $name = '';
        $date = new \DateTimeImmutable('2025-01-29');
        $type = 'invalid';

        $this->service->createHoliday($name, $date, $type);
    }

    public function testUpdateHolidayConfig(): void
    {
        $configId = 1;
        $updateData = [
            'name' => '国庆节',
        ];

        $existingConfig = new HolidayConfig();
        $existingConfig->setName('春节');
        $existingConfig->setHolidayDate(new \DateTimeImmutable('2025-01-29'));
        $existingConfig->setType('national');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($configId)
            ->willReturn($existingConfig)
        ;

        $this->repository->expects($this->once())
            ->method('save')
            ->with($existingConfig, true)
        ;

        $config = $this->service->updateHoliday($configId, $updateData);

        $this->assertEquals('国庆节', $config->getName());
        $this->assertEquals('national', $config->getType());
    }

    public function testUpdateHolidayConfigNotFound(): void
    {
        $configId = 999;
        $updateData = ['name' => '国庆节'];

        $this->repository->expects($this->once())
            ->method('find')
            ->with($configId)
            ->willReturn(null)
        ;

        $this->expectException(AttendanceException::class);

        $this->service->updateHoliday($configId, $updateData);
    }

    public function testDeleteHolidayConfig(): void
    {
        $configId = 1;
        $existingConfig = new HolidayConfig();
        $existingConfig->setName('春节');
        $existingConfig->setHolidayDate(new \DateTimeImmutable('2025-01-29'));
        $existingConfig->setType('national');

        $this->repository->expects($this->once())
            ->method('find')
            ->with($configId)
            ->willReturn($existingConfig)
        ;

        $this->repository->expects($this->once())
            ->method('save')
            ->with($existingConfig, true)
        ;

        $this->service->deleteHoliday($configId);

        // 验证假期被标记为非活动状态
        $this->assertFalse($existingConfig->isActive());
    }

    public function testDeleteHolidayConfigNotFound(): void
    {
        $configId = 999;

        $this->repository->expects($this->once())
            ->method('find')
            ->with($configId)
            ->willReturn(null)
        ;

        $this->expectException(AttendanceException::class);

        $this->service->deleteHoliday($configId);
    }

    public function testIsHoliday(): void
    {
        $date = new \DateTimeImmutable('2025-01-29');

        $holiday = new HolidayConfig();
        $holiday->setName('春节');
        $holiday->setHolidayDate(new \DateTimeImmutable('2025-01-29'));
        $holiday->setType('national');

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['holidayDate' => $date, 'isActive' => true])
            ->willReturn($holiday)
        ;

        $isHoliday = $this->service->isHoliday($date);

        $this->assertTrue($isHoliday);
    }

    public function testIsNotHoliday(): void
    {
        $date = new \DateTimeImmutable('2025-03-15');

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['holidayDate' => $date, 'isActive' => true])
            ->willReturn(null)
        ;

        $isHoliday = $this->service->isHoliday($date);

        $this->assertFalse($isHoliday);
    }

    public function testIsWorkingDay(): void
    {
        $date = new \DateTimeImmutable('2025-03-17'); // 周一

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['holidayDate' => $date, 'isActive' => true])
            ->willReturn(null)
        ;

        $isWorkingDay = $this->service->isWorkingDay($date);

        $this->assertTrue($isWorkingDay);
    }

    public function testIsNotWorkingDayWeekend(): void
    {
        $date = new \DateTimeImmutable('2025-03-15'); // 周六

        // 不需要调用repository，因为是周末直接返回false
        $this->repository->expects($this->never())
            ->method('findOneBy')
        ;

        $isWorkingDay = $this->service->isWorkingDay($date);

        $this->assertFalse($isWorkingDay);
    }

    public function testIsNotWorkingDayHoliday(): void
    {
        $date = new \DateTimeImmutable('2025-01-29'); // 周三，但是假期

        $holiday = new HolidayConfig();
        $holiday->setName('春节');
        $holiday->setHolidayDate(new \DateTimeImmutable('2025-01-29'));
        $holiday->setType('national');

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['holidayDate' => $date, 'isActive' => true])
            ->willReturn($holiday)
        ;

        $isWorkingDay = $this->service->isWorkingDay($date);

        $this->assertFalse($isWorkingDay);
    }

    public function testGetHolidaysByDateRange(): void
    {
        $startDate = new \DateTimeImmutable('2025-01-01');
        $endDate = new \DateTimeImmutable('2025-12-31');

        $holiday1 = new HolidayConfig();
        $holiday1->setName('春节');
        $holiday1->setHolidayDate(new \DateTimeImmutable('2025-01-29'));
        $holiday1->setType('national');

        $holiday2 = new HolidayConfig();
        $holiday2->setName('国庆节');
        $holiday2->setHolidayDate(new \DateTimeImmutable('2025-10-01'));
        $holiday2->setType('national');

        $holidays = [$holiday1, $holiday2];

        $this->repository->expects($this->once())
            ->method('findByDateRange')
            ->with($startDate, $endDate)
            ->willReturn($holidays)
        ;

        $result = $this->service->getHolidaysByDateRange($startDate, $endDate);

        $this->assertCount(2, $result);
        $this->assertEquals('春节', $result[0]->getName());
        $this->assertEquals('国庆节', $result[1]->getName());
    }

    public function testGetHolidaysByType(): void
    {
        $type = 'national';

        $holiday1 = new HolidayConfig();
        $holiday1->setName('春节');
        $holiday1->setHolidayDate(new \DateTimeImmutable('2025-01-29'));
        $holiday1->setType('national');

        $holiday2 = new HolidayConfig();
        $holiday2->setName('国庆节');
        $holiday2->setHolidayDate(new \DateTimeImmutable('2025-10-01'));
        $holiday2->setType('national');

        $holidays = [$holiday1, $holiday2];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['type' => $type, 'isActive' => true], ['holidayDate' => 'ASC'])
            ->willReturn($holidays)
        ;

        $result = $this->service->getHolidaysByType($type);

        $this->assertCount(2, $result);
        $this->assertEquals('national', $result[0]->getType());
        $this->assertEquals('national', $result[1]->getType());
    }

    public function testValidateHolidayData(): void
    {
        $validData = [
            'name' => '春节',
            'type' => 'national',
            'date' => new \DateTimeImmutable('2025-01-29'),
        ];

        $isValid = $this->service->validateHolidayData($validData);

        $this->assertTrue($isValid);
    }

    public function testValidateHolidayDataInvalidName(): void
    {
        $invalidData = [
            'name' => '',
            'type' => 'national',
            'date' => new \DateTimeImmutable('2025-01-29'),
        ];

        $isValid = $this->service->validateHolidayData($invalidData);

        $this->assertFalse($isValid);
    }

    public function testValidateHolidayDataInvalidType(): void
    {
        $invalidData = [
            'name' => '春节',
            'type' => 'invalid_type',
            'date' => new \DateTimeImmutable('2025-01-29'),
        ];

        $isValid = $this->service->validateHolidayData($invalidData);

        $this->assertFalse($isValid);
    }

    public function testValidateHolidayDataInvalidDate(): void
    {
        $invalidData = [
            'name' => '春节',
            'type' => 'national',
            'date' => 'invalid_date',
        ];

        $isValid = $this->service->validateHolidayData($invalidData);

        $this->assertFalse($isValid);
    }

    public function testGetHolidayConfig(): void
    {
        $configId = 1;
        $holiday = new HolidayConfig();
        $holiday->setName('春节');
        $holiday->setHolidayDate(new \DateTimeImmutable('2025-01-29'));
        $holiday->setType('national');

        // 使用反射设置id属性
        $reflection = new \ReflectionClass($holiday);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($holiday, $configId);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($configId)
            ->willReturn($holiday)
        ;

        $result = $this->service->getHolidayConfig($configId);

        $this->assertIsArray($result);
        $this->assertEquals('春节', $result['name']);
        $this->assertEquals('national', $result['type']);
    }

    public function testGetHolidayConfigNotFound(): void
    {
        $configId = 999;

        $this->repository->expects($this->once())
            ->method('find')
            ->with($configId)
            ->willReturn(null)
        ;

        $result = $this->service->getHolidayConfig($configId);

        $this->assertNull($result);
    }
}
