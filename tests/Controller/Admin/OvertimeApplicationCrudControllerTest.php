<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\AttendanceManageBundle\Controller\Admin\OvertimeApplicationCrudController;
use Tourze\AttendanceManageBundle\Entity\OvertimeApplication;
use Tourze\AttendanceManageBundle\Enum\ApplicationStatus;
use Tourze\AttendanceManageBundle\Enum\OvertimeType;
use Tourze\AttendanceManageBundle\Repository\OvertimeApplicationRepository;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(OvertimeApplicationCrudController::class)]
#[RunTestsInSeparateProcesses]
final class OvertimeApplicationCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getEntityFqcn(): string
    {
        return OvertimeApplication::class;
    }

    protected function getControllerService(): OvertimeApplicationCrudController
    {
        return self::getService(OvertimeApplicationCrudController::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield '员工ID' => ['员工ID'];
        yield '加班日期' => ['加班日期'];
        yield '加班时长(小时)' => ['加班时长(小时)'];
        yield '加班类型' => ['加班类型'];
        yield '审批状态' => ['审批状态'];
        yield '补偿方式' => ['补偿方式'];
    }

    public function testIndexPage(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);
        $crawler = $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Navigate to OvertimeApplication CRUD
        $link = $crawler->filter('a[href*="OvertimeApplicationCrudController"]')->first();
        if ($link->count() > 0) {
            $client->click($link->link());
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
        }
    }

    public function testCreateOvertimeApplication(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);
        $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Test creating new overtime application
        $overtimeApplication = new OvertimeApplication();
        $overtimeApplication->setEmployeeId(1);
        $overtimeApplication->setOvertimeDate(new \DateTimeImmutable('2024-03-15'));
        $overtimeApplication->setStartTime(new \DateTimeImmutable('2024-03-15 18:00:00'));
        $overtimeApplication->setEndTime(new \DateTimeImmutable('2024-03-15 22:00:00'));
        $overtimeApplication->setDuration(4.0);
        $overtimeApplication->setOvertimeType(OvertimeType::WORKDAY);
        $overtimeApplication->setReason('项目紧急需求开发');

        $repository = self::getService(OvertimeApplicationRepository::class);
        self::assertInstanceOf(OvertimeApplicationRepository::class, $repository);
        $repository->save($overtimeApplication, true);

        // Verify overtime application was created
        $savedApplication = self::getEntityManager()->getRepository(OvertimeApplication::class)->findOneBy([
            'employeeId' => 1,
            'overtimeDate' => new \DateTimeImmutable('2024-03-15'),
        ]);
        $this->assertNotNull($savedApplication);
        $this->assertEquals(1, $savedApplication->getEmployeeId());
        $this->assertEquals(4.0, $savedApplication->getDuration());
        $this->assertEquals('项目紧急需求开发', $savedApplication->getReason());
        $this->assertEquals(ApplicationStatus::PENDING, $savedApplication->getStatus());
    }

    public function testOvertimeApplicationDataPersistence(): void
    {
        // Create client to initialize database
        $client = self::createClientWithDatabase();

        // Create test overtime applications with different statuses
        $application1 = new OvertimeApplication();
        $application1->setEmployeeId(1);
        $application1->setOvertimeDate(new \DateTimeImmutable('2024-03-20'));
        $application1->setStartTime(new \DateTimeImmutable('2024-03-20 19:00:00'));
        $application1->setEndTime(new \DateTimeImmutable('2024-03-20 23:00:00'));
        $application1->setDuration(4.0);
        $application1->setOvertimeType(OvertimeType::WORKDAY);
        $application1->setReason('系统维护升级');
        $application1->approve(1); // approverId

        $repository = self::getService(OvertimeApplicationRepository::class);
        self::assertInstanceOf(OvertimeApplicationRepository::class, $repository);
        $repository->save($application1, true);

        $application2 = new OvertimeApplication();
        $application2->setEmployeeId(2);
        $application2->setOvertimeDate(new \DateTimeImmutable('2024-03-22'));
        $application2->setStartTime(new \DateTimeImmutable('2024-03-22 20:00:00'));
        $application2->setEndTime(new \DateTimeImmutable('2024-03-22 23:59:59'));
        $application2->setDuration(4.0);
        $application2->setOvertimeType(OvertimeType::WORKDAY);
        $application2->setReason('客户紧急需求');
        $application2->reject(2); // approverId

        $repository->save($application2, true);

        // Verify applications are saved correctly
        $savedApplication1 = $repository->findOneBy([
            'employeeId' => 1,
            'overtimeDate' => new \DateTimeImmutable('2024-03-20'),
        ]);
        $this->assertNotNull($savedApplication1);
        $this->assertEquals(1, $savedApplication1->getEmployeeId());
        $this->assertEquals(4.0, $savedApplication1->getDuration());
        $this->assertEquals(ApplicationStatus::APPROVED, $savedApplication1->getStatus());
        $this->assertEquals(1, $savedApplication1->getApproverId());
        $this->assertNotNull($savedApplication1->getApproveTime());

        $savedApplication2 = $repository->findOneBy([
            'employeeId' => 2,
            'overtimeDate' => new \DateTimeImmutable('2024-03-22'),
        ]);
        $this->assertNotNull($savedApplication2);
        $this->assertEquals(2, $savedApplication2->getEmployeeId());
        $this->assertEquals(ApplicationStatus::REJECTED, $savedApplication2->getStatus());
        $this->assertEquals(2, $savedApplication2->getApproverId());
        $this->assertNotNull($savedApplication2->getApproveTime());
    }

    public function testOvertimeApplicationValidation(): void
    {
        $client = self::createClientWithDatabase();

        // Test validation constraints
        $repository = self::getService(OvertimeApplicationRepository::class);
        self::assertInstanceOf(OvertimeApplicationRepository::class, $repository);

        // Test valid overtime application
        $validApplication = new OvertimeApplication();
        $validApplication->setEmployeeId(301);
        $validApplication->setOvertimeDate(new \DateTimeImmutable('2024-04-10'));
        $validApplication->setStartTime(new \DateTimeImmutable('2024-04-10 18:30:00'));
        $validApplication->setEndTime(new \DateTimeImmutable('2024-04-10 21:30:00'));
        $validApplication->setDuration(3.0);
        $validApplication->setOvertimeType(OvertimeType::WORKDAY);
        $validApplication->setReason('测试月末报表整理');
        $validApplication->approve(3);

        $repository->save($validApplication, true);
        $savedApplication = $repository->findOneBy(['employeeId' => 301, 'duration' => 3.0]);
        $this->assertNotNull($savedApplication);
        $this->assertEquals(3.0, $savedApplication->getDuration());
        $this->assertEquals('测试月末报表整理', $savedApplication->getReason());
    }

    public function testOvertimeApplicationTimeCalculation(): void
    {
        $client = self::createClientWithDatabase();

        $repository = self::getService(OvertimeApplicationRepository::class);
        self::assertInstanceOf(OvertimeApplicationRepository::class, $repository);

        // Test different overtime durations
        $shortOvertimeApplication = new OvertimeApplication();
        $shortOvertimeApplication->setEmployeeId(4);
        $shortOvertimeApplication->setOvertimeDate(new \DateTimeImmutable('2024-05-05'));
        $shortOvertimeApplication->setStartTime(new \DateTimeImmutable('2024-05-05 18:00:00'));
        $shortOvertimeApplication->setEndTime(new \DateTimeImmutable('2024-05-05 19:30:00'));
        $shortOvertimeApplication->setDuration(1.5);
        $shortOvertimeApplication->setOvertimeType(OvertimeType::WORKDAY);
        $shortOvertimeApplication->setReason('紧急修复bug');

        $repository->save($shortOvertimeApplication, true);
        $savedApplication = $repository->findOneBy(['employeeId' => 4, 'duration' => 1.5]);
        $this->assertNotNull($savedApplication);
        $this->assertEquals(1.5, $savedApplication->getDuration());
        $this->assertEquals('紧急修复bug', $savedApplication->getReason());
    }

    public function testOvertimeApplicationWeekendWork(): void
    {
        $client = self::createClientWithDatabase();

        $repository = self::getService(OvertimeApplicationRepository::class);
        self::assertInstanceOf(OvertimeApplicationRepository::class, $repository);

        // Test weekend overtime (Saturday)
        $weekendApplication = new OvertimeApplication();
        $weekendApplication->setEmployeeId(5);
        $weekendApplication->setOvertimeDate(new \DateTimeImmutable('2024-03-16')); // Saturday
        $weekendApplication->setStartTime(new \DateTimeImmutable('2024-03-16 09:00:00'));
        $weekendApplication->setEndTime(new \DateTimeImmutable('2024-03-16 17:00:00'));
        $weekendApplication->setDuration(8.0);
        $weekendApplication->setOvertimeType(OvertimeType::WEEKEND);
        $weekendApplication->setReason('周末项目部署');
        $weekendApplication->setStatus(ApplicationStatus::APPROVED);

        $repository->save($weekendApplication, true);
        $savedApplication = $repository->findOneBy(['employeeId' => 5, 'duration' => 8.0]);
        $this->assertNotNull($savedApplication);
        $this->assertEquals(8.0, $savedApplication->getDuration());
        $this->assertEquals('周末项目部署', $savedApplication->getReason());
    }

    public function testValidApplicationHasNoViolations(): void
    {
        $client = self::createClientWithDatabase();
        /** @var ValidatorInterface $validator */
        $validator = self::getService(ValidatorInterface::class);

        $validApplication = new OvertimeApplication();
        $validApplication->setEmployeeId(1);
        $validApplication->setOvertimeDate(new \DateTimeImmutable('2024-03-15'));
        $validApplication->setStartTime(new \DateTimeImmutable('2024-03-15 18:00:00'));
        $validApplication->setEndTime(new \DateTimeImmutable('2024-03-15 22:00:00'));
        $validApplication->setDuration(4.0);
        $validApplication->setOvertimeType(OvertimeType::WORKDAY);
        $validApplication->setReason('紧急项目开发');

        $violations = $validator->validate($validApplication);
        $this->assertCount(0, $violations, '有效申请不应有验证错误');
    }

    public function testEmployeeIdCannotBeInvalid(): void
    {
        $client = self::createClientWithDatabase();
        /** @var ValidatorInterface $validator */
        $validator = self::getService(ValidatorInterface::class);

        $application = new OvertimeApplication();
        $application->setEmployeeId(1);
        $application->setOvertimeDate(new \DateTimeImmutable('2024-03-15'));
        $application->setStartTime(new \DateTimeImmutable('2024-03-15 18:00:00'));
        $application->setEndTime(new \DateTimeImmutable('2024-03-15 22:00:00'));
        $application->setDuration(4.0);
        $application->setOvertimeType(OvertimeType::WORKDAY);
        $application->setReason('紧急项目开发');

        $reflection = new \ReflectionClass($application);
        $employeeIdProperty = $reflection->getProperty('employeeId');
        $employeeIdProperty->setAccessible(true);
        $employeeIdProperty->setValue($application, 0);

        $violations = $validator->validate($application);
        $this->assertGreaterThan(0, $violations->count(), '员工ID为0时应有验证错误');

        $hasEmployeeIdViolation = false;
        foreach ($violations as $violation) {
            if ('employeeId' === $violation->getPropertyPath()) {
                $hasEmployeeIdViolation = true;
                $this->assertStringContainsString('正数', (string) $violation->getMessage());
                break;
            }
        }
        $this->assertTrue($hasEmployeeIdViolation, '应包含员工ID验证错误');
    }

    public function testDurationCannotBeInvalid(): void
    {
        $client = self::createClientWithDatabase();
        /** @var ValidatorInterface $validator */
        $validator = self::getService(ValidatorInterface::class);

        $application = new OvertimeApplication();
        $application->setEmployeeId(1);
        $application->setOvertimeDate(new \DateTimeImmutable('2024-03-15'));
        $application->setStartTime(new \DateTimeImmutable('2024-03-15 18:00:00'));
        $application->setEndTime(new \DateTimeImmutable('2024-03-15 22:00:00'));
        $application->setDuration(4.0);
        $application->setOvertimeType(OvertimeType::WORKDAY);
        $application->setReason('紧急项目开发');

        $reflection = new \ReflectionClass($application);
        $durationProperty = $reflection->getProperty('duration');
        $durationProperty->setAccessible(true);
        $durationProperty->setValue($application, 0);

        $violations = $validator->validate($application);
        $this->assertGreaterThan(0, $violations->count(), '加班时长为0时应有验证错误');

        $hasDurationViolation = false;
        foreach ($violations as $violation) {
            if ('duration' === $violation->getPropertyPath()) {
                $hasDurationViolation = true;
                $this->assertStringContainsString('正数', (string) $violation->getMessage());
                break;
            }
        }
        $this->assertTrue($hasDurationViolation, '应包含加班时长验证错误');
    }

    public function testEndTimeMustBeAfterStartTime(): void
    {
        $client = self::createClientWithDatabase();
        /** @var ValidatorInterface $validator */
        $validator = self::getService(ValidatorInterface::class);

        $application = new OvertimeApplication();
        $application->setEmployeeId(1);
        $application->setOvertimeDate(new \DateTimeImmutable('2024-03-15'));
        $application->setStartTime(new \DateTimeImmutable('2024-03-15 18:00:00'));
        $application->setEndTime(new \DateTimeImmutable('2024-03-15 22:00:00'));
        $application->setDuration(4.0);
        $application->setOvertimeType(OvertimeType::WORKDAY);
        $application->setReason('紧急项目开发');

        $reflection = new \ReflectionClass($application);
        $endTimeProperty = $reflection->getProperty('endTime');
        $endTimeProperty->setAccessible(true);
        $endTimeProperty->setValue($application, new \DateTimeImmutable('2024-03-15 17:00:00')); // Before start time

        $violations = $validator->validate($application);
        $this->assertGreaterThan(0, $violations->count(), '结束时间在开始时间之前时应有验证错误');

        $hasEndTimeViolation = false;
        foreach ($violations as $violation) {
            if ('endTime' === $violation->getPropertyPath()) {
                $hasEndTimeViolation = true;
                $this->assertStringContainsString('必须晚于', (string) $violation->getMessage());
                break;
            }
        }
        $this->assertTrue($hasEndTimeViolation, '应包含结束时间验证错误');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'employeeId' => ['employeeId'];
        yield 'overtimeDate' => ['overtimeDate'];
        yield 'startTime' => ['startTime'];
        yield 'endTime' => ['endTime'];
        yield 'duration' => ['duration'];
        yield 'overtimeType' => ['overtimeType'];
        yield 'reason' => ['reason'];
        yield 'compensationType' => ['compensationType'];
        yield 'status' => ['status'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'employeeId' => ['employeeId'];
        yield 'overtimeDate' => ['overtimeDate'];
        yield 'startTime' => ['startTime'];
        yield 'endTime' => ['endTime'];
        yield 'duration' => ['duration'];
        yield 'overtimeType' => ['overtimeType'];
        yield 'reason' => ['reason'];
        yield 'compensationType' => ['compensationType'];
        yield 'status' => ['status'];
        yield 'approveTime' => ['approveTime'];
        yield 'approverId' => ['approverId'];
    }

    public function testApproveApplication(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create test entity first
        $repository = self::getService(OvertimeApplicationRepository::class);
        self::assertInstanceOf(OvertimeApplicationRepository::class, $repository);

        $entity = new OvertimeApplication();
        $entity->setEmployeeId(2001);
        $entity->setOvertimeDate(new \DateTimeImmutable('2024-06-10'));
        $entity->setStartTime(new \DateTimeImmutable('2024-06-10 18:00:00'));
        $entity->setEndTime(new \DateTimeImmutable('2024-06-10 22:00:00'));
        $entity->setDuration(4.0);
        $entity->setOvertimeType(OvertimeType::WORKDAY);
        $entity->setReason('测试批准动作');
        $repository->save($entity, true);

        // Test approve action
        $client->request('GET', sprintf('/admin/attendance/overtime-applications/%d/approve', $entity->getId()));
        $response = $client->getResponse();
        $this->assertTrue($response->isRedirection(), 'Response should be a redirect');

        // Verify the application status was updated
        $updatedEntity = $repository->find($entity->getId());
        $this->assertNotNull($updatedEntity);
        $this->assertTrue($updatedEntity->isApproved(), 'Application should be approved');
    }

    public function testRejectApplication(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create test entity first
        $repository = self::getService(OvertimeApplicationRepository::class);
        self::assertInstanceOf(OvertimeApplicationRepository::class, $repository);

        $entity = new OvertimeApplication();
        $entity->setEmployeeId(2002);
        $entity->setOvertimeDate(new \DateTimeImmutable('2024-06-15'));
        $entity->setStartTime(new \DateTimeImmutable('2024-06-15 19:00:00'));
        $entity->setEndTime(new \DateTimeImmutable('2024-06-15 23:00:00'));
        $entity->setDuration(4.0);
        $entity->setOvertimeType(OvertimeType::WORKDAY);
        $entity->setReason('测试拒绝动作');
        $repository->save($entity, true);

        // Test reject action
        $client->request('GET', sprintf('/admin/attendance/overtime-applications/%d/reject', $entity->getId()));
        $response = $client->getResponse();
        $this->assertTrue($response->isRedirection(), 'Response should be a redirect');

        // Verify the application status was updated
        $updatedEntity = $repository->find($entity->getId());
        $this->assertNotNull($updatedEntity);
        $this->assertTrue($updatedEntity->isRejected(), 'Application should be rejected');
    }

    public function testValidationErrors(): void
    {
        $client = self::createClientWithDatabase();
        /** @var ValidatorInterface $validator */
        $validator = self::getService(ValidatorInterface::class);

        // Test invalid employee ID validation error - should not be blank
        $invalidApplication = new OvertimeApplication();
        $invalidApplication->setEmployeeId(0); // Invalid employee ID
        $invalidApplication->setOvertimeDate(new \DateTimeImmutable('2024-03-15'));
        $invalidApplication->setStartTime(new \DateTimeImmutable('2024-03-15 18:00:00'));
        $invalidApplication->setEndTime(new \DateTimeImmutable('2024-03-15 22:00:00'));
        $invalidApplication->setDuration(4.0);
        $invalidApplication->setOvertimeType(OvertimeType::WORKDAY);
        $invalidApplication->setReason('紧急项目开发');

        $violations = $validator->validate($invalidApplication);
        $this->assertGreaterThan(0, $violations->count(), '无效员工ID应该有验证错误 - should not be blank');

        // Verify the violation is for the employeeId field
        $employeeIdViolations = [];
        foreach ($violations as $violation) {
            if ('employeeId' === $violation->getPropertyPath()) {
                $employeeIdViolations[] = $violation;
            }
        }
        $this->assertGreaterThan(0, count($employeeIdViolations), '应该存在员工ID字段的验证错误');
    }
}
