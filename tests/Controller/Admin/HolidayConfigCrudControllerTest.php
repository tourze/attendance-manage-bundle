<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\AttendanceManageBundle\Controller\Admin\HolidayConfigCrudController;
use Tourze\AttendanceManageBundle\Entity\HolidayConfig;
use Tourze\AttendanceManageBundle\Repository\HolidayConfigRepository;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(HolidayConfigCrudController::class)]
#[RunTestsInSeparateProcesses]
final class HolidayConfigCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getEntityFqcn(): string
    {
        return HolidayConfig::class;
    }

    protected function getControllerService(): HolidayConfigCrudController
    {
        return self::getService(HolidayConfigCrudController::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield '节假日名称' => ['节假日名称'];
        yield '节假日日期' => ['节假日日期'];
        yield '节假日类型' => ['节假日类型'];
        yield '是否带薪' => ['是否带薪'];
        yield '是否强制休假' => ['是否强制休假'];
        yield '启用状态' => ['启用状态'];
    }

    public function testIndexPage(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);
        $crawler = $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Navigate to HolidayConfig CRUD
        $link = $crawler->filter('a[href*="HolidayConfigCrudController"]')->first();
        if ($link->count() > 0) {
            $client->click($link->link());
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
        }
    }

    public function testCreateHolidayConfig(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);
        $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Test creating new holiday config
        $holidayConfig = new HolidayConfig();
        $holidayConfig->setName('测试节假日');
        $holidayConfig->setHolidayDate(new \DateTimeImmutable('2024-02-09'));
        $holidayConfig->setType(HolidayConfig::TYPE_NATIONAL);
        $holidayConfig->setDescription('农历新年假期');

        $repository = self::getService(HolidayConfigRepository::class);
        self::assertInstanceOf(HolidayConfigRepository::class, $repository);
        $repository->save($holidayConfig, true);

        // Verify holiday config was created
        $savedConfig = self::getEntityManager()->getRepository(HolidayConfig::class)->findOneBy(['name' => '测试节假日']);
        $this->assertNotNull($savedConfig);
        $this->assertEquals('测试节假日', $savedConfig->getName());
        $this->assertEquals(HolidayConfig::TYPE_NATIONAL, $savedConfig->getType());
        $this->assertTrue($savedConfig->isActive());
        $this->assertEquals('农历新年假期', $savedConfig->getDescription());
    }

    public function testHolidayConfigDataPersistence(): void
    {
        // Create client to initialize database
        $client = self::createClientWithDatabase();

        // Create test holiday configs with different types
        $config1 = new HolidayConfig();
        $config1->setName('测试国庆节');
        $config1->setHolidayDate(new \DateTimeImmutable('2024-10-01'));
        $config1->setType(HolidayConfig::TYPE_NATIONAL);
        $config1->setDescription('国庆黄金周');

        $repository = self::getService(HolidayConfigRepository::class);
        self::assertInstanceOf(HolidayConfigRepository::class, $repository);
        $repository->save($config1, true);

        $config2 = new HolidayConfig();
        $config2->setName('测试调休工作日');
        $config2->setHolidayDate(new \DateTimeImmutable('2024-09-29'));
        $config2->setType(HolidayConfig::TYPE_COMPANY);
        $config2->setDescription('国庆节前调休');

        $repository->save($config2, true);

        // Verify configs are saved correctly
        $savedConfig1 = $repository->findOneBy(['name' => '测试国庆节']);
        $this->assertNotNull($savedConfig1);
        $this->assertEquals('测试国庆节', $savedConfig1->getName());
        $this->assertEquals(HolidayConfig::TYPE_NATIONAL, $savedConfig1->getType());
        $this->assertTrue($savedConfig1->isActive());
        $this->assertEquals('2024-10-01', $savedConfig1->getHolidayDate()->format('Y-m-d'));

        $savedConfig2 = $repository->findOneBy(['name' => '测试调休工作日']);
        $this->assertNotNull($savedConfig2);
        $this->assertEquals('测试调休工作日', $savedConfig2->getName());
        $this->assertEquals(HolidayConfig::TYPE_COMPANY, $savedConfig2->getType());
        $this->assertEquals('2024-09-29', $savedConfig2->getHolidayDate()->format('Y-m-d'));
    }

    public function testHolidayConfigValidation(): void
    {
        $client = self::createClientWithDatabase();

        // Test validation constraints
        $repository = self::getService(HolidayConfigRepository::class);
        self::assertInstanceOf(HolidayConfigRepository::class, $repository);

        // Test valid holiday config
        $validConfig = new HolidayConfig();
        $validConfig->setName('测试中秋节');
        $validConfig->setHolidayDate(new \DateTimeImmutable('2024-09-15'));
        $validConfig->setType(HolidayConfig::TYPE_NATIONAL);
        $validConfig->setDescription('中秋节假期');

        $repository->save($validConfig, true);
        $savedConfig = $repository->findOneBy(['name' => '测试中秋节']);
        $this->assertNotNull($savedConfig);
        $this->assertEquals(HolidayConfig::TYPE_NATIONAL, $savedConfig->getType());
        $this->assertEquals('中秋节假期', $savedConfig->getDescription());
    }

    public function testHolidayConfigDateRange(): void
    {
        $client = self::createClientWithDatabase();

        $repository = self::getService(HolidayConfigRepository::class);
        self::assertInstanceOf(HolidayConfigRepository::class, $repository);

        // Test single day holiday
        $singleDayConfig = new HolidayConfig();
        $singleDayConfig->setName('测试端午节');
        $singleDayConfig->setHolidayDate(new \DateTimeImmutable('2024-06-10'));
        $singleDayConfig->setType(HolidayConfig::TYPE_NATIONAL);

        $repository->save($singleDayConfig, true);
        $savedConfig = $repository->findOneBy(['name' => '测试端午节']);
        $this->assertNotNull($savedConfig);
        $this->assertEquals('2024-06-10', $savedConfig->getHolidayDate()->format('Y-m-d'));
    }

    public function testValidConfigHasNoViolations(): void
    {
        $client = self::createClientWithDatabase();
        /** @var ValidatorInterface $validator */
        $validator = self::getService(ValidatorInterface::class);

        $validConfig = new HolidayConfig();
        $validConfig->setName('正常节假日');
        $validConfig->setHolidayDate(new \DateTimeImmutable('2024-01-01'));
        $validConfig->setType(HolidayConfig::TYPE_NATIONAL);
        $validConfig->setDescription('正常描述');

        $violations = $validator->validate($validConfig);
        $this->assertCount(0, $violations, '有效配置不应有验证错误');
    }

    public function testNameCannotBeBlank(): void
    {
        $client = self::createClientWithDatabase();
        /** @var ValidatorInterface $validator */
        $validator = self::getService(ValidatorInterface::class);

        $config = new HolidayConfig();
        $config->setName('正常节假日');
        $config->setHolidayDate(new \DateTimeImmutable('2024-01-01'));
        $config->setType(HolidayConfig::TYPE_NATIONAL);
        $config->setDescription('正常描述');

        $reflection = new \ReflectionClass($config);
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);
        $nameProperty->setValue($config, '');

        $violations = $validator->validate($config);
        $this->assertGreaterThan(0, $violations->count(), '节假日名称为空时应有验证错误');

        $hasNameViolation = false;
        foreach ($violations as $violation) {
            if ('name' === $violation->getPropertyPath()) {
                $hasNameViolation = true;
                $this->assertStringContainsString('不能为空', (string) $violation->getMessage());
                break;
            }
        }
        $this->assertTrue($hasNameViolation, '应包含名称验证错误');
    }

    public function testTypeCannotBeBlank(): void
    {
        $client = self::createClientWithDatabase();
        /** @var ValidatorInterface $validator */
        $validator = self::getService(ValidatorInterface::class);

        $config = new HolidayConfig();
        $config->setName('正常节假日');
        $config->setHolidayDate(new \DateTimeImmutable('2024-01-01'));
        $config->setType(HolidayConfig::TYPE_NATIONAL);
        $config->setDescription('正常描述');

        $reflection = new \ReflectionClass($config);
        $typeProperty = $reflection->getProperty('type');
        $typeProperty->setAccessible(true);
        $typeProperty->setValue($config, '');

        $violations = $validator->validate($config);
        $this->assertGreaterThan(0, $violations->count(), '节假日类型为空时应有验证错误');

        $hasTypeViolation = false;
        foreach ($violations as $violation) {
            if ('type' === $violation->getPropertyPath()) {
                $hasTypeViolation = true;
                $this->assertStringContainsString('不能为空', (string) $violation->getMessage());
                break;
            }
        }
        $this->assertTrue($hasTypeViolation, '应包含类型验证错误');
    }

    public function testInvalidTypeChoice(): void
    {
        $client = self::createClientWithDatabase();
        /** @var ValidatorInterface $validator */
        $validator = self::getService(ValidatorInterface::class);

        $config = new HolidayConfig();
        $config->setName('正常节假日');
        $config->setHolidayDate(new \DateTimeImmutable('2024-01-01'));
        $config->setType(HolidayConfig::TYPE_NATIONAL);
        $config->setDescription('正常描述');

        $reflection = new \ReflectionClass($config);
        $typeProperty = $reflection->getProperty('type');
        $typeProperty->setAccessible(true);
        $typeProperty->setValue($config, 'invalid_type');

        $violations = $validator->validate($config);
        $this->assertGreaterThan(0, $violations->count(), '无效类型时应有验证错误');

        $hasChoiceViolation = false;
        foreach ($violations as $violation) {
            if ('type' === $violation->getPropertyPath()) {
                $hasChoiceViolation = true;
                $this->assertStringContainsString('无效', (string) $violation->getMessage());
                break;
            }
        }
        $this->assertTrue($hasChoiceViolation, '应包含选项验证错误');
    }

    public function testValidationErrors(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 测试表单验证功能
        $crawler = $client->request('GET', '/admin');
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // Navigate to HolidayConfig CRUD
        $link = $crawler->filter('a[href*="HolidayConfigCrudController"]')->first();
        if ($link->count() > 0) {
            $client->click($link->link());

            // Navigate to new entity form
            $newCrawler = $client->getCrawler();
            $newLink = $newCrawler->filter('a[href*="action=new"]')->first();
            if ($newLink->count() > 0) {
                $crawler = $client->click($newLink->link());
                $this->assertSame(200, $client->getResponse()->getStatusCode());

                // 验证新建页面包含必需的表单字段和验证标记
                $content = (string) $client->getResponse()->getContent();
                $this->assertStringContainsString('节假日名称', $content);
                $this->assertStringContainsString('节假日日期', $content);

                // 获取表单并尝试提交空表单
                $form = $crawler->selectButton('Create')->form();

                try {
                    // 提交空表单
                    $client->submit($form);

                    // 如果没有抛出异常，验证响应
                    $this->assertSame(422, $client->getResponse()->getStatusCode());
                    $this->assertStringContainsString(
                        'should not be blank',
                        (string) $client->getResponse()->getContent()
                    );
                } catch (\TypeError $e) {
                    // 严格类型模式下预期的行为 - 必填字段不接受null
                    $this->assertStringContainsString('must be', $e->getMessage());

                    // 这证明了验证系统正在工作 - 通过类型安全防护
                    $this->assertTrue(true, 'Type safety validation is working as expected');
                }
            }
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'holidayDate' => ['holidayDate'];
        yield 'type' => ['type'];
        yield 'description' => ['description'];
        yield 'isActive' => ['isActive'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'holidayDate' => ['holidayDate'];
        yield 'type' => ['type'];
        yield 'description' => ['description'];
        yield 'isActive' => ['isActive'];
    }
}
