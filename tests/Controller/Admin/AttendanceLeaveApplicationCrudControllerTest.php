<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\AttendanceManageBundle\Controller\Admin\AttendanceLeaveApplicationCrudController;
use Tourze\AttendanceManageBundle\Entity\LeaveApplication;
use Tourze\AttendanceManageBundle\Enum\ApplicationStatus;
use Tourze\AttendanceManageBundle\Enum\LeaveType;
use Tourze\AttendanceManageBundle\Repository\LeaveApplicationRepository;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(AttendanceLeaveApplicationCrudController::class)]
#[RunTestsInSeparateProcesses]
final class AttendanceLeaveApplicationCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getEntityFqcn(): string
    {
        return LeaveApplication::class;
    }

    protected function getControllerService(): AttendanceLeaveApplicationCrudController
    {
        return self::getService(AttendanceLeaveApplicationCrudController::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield '员工ID' => ['员工ID'];
        yield '请假类型' => ['请假类型'];
        yield '请假开始时间' => ['请假开始时间'];
        yield '请假结束时间' => ['请假结束时间'];
        yield '请假天数' => ['请假天数'];
        yield '审批状态' => ['审批状态'];
    }

    public function testIndexPage(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);
        $crawler = $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Navigate to LeaveApplication CRUD
        $link = $crawler->filter('a[href*="AttendanceLeaveApplicationCrudController"]')->first();
        if ($link->count() > 0) {
            $client->click($link->link());
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
        }
    }

    public function testCreateLeaveApplication(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);
        $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Test creating new leave application - use unique employeeId to avoid fixture conflicts
        $leaveApplication = new LeaveApplication();
        $leaveApplication->setEmployeeId(999); // Use unique employee ID
        $leaveApplication->setLeaveType(LeaveType::ANNUAL);
        $leaveApplication->setStartDate(new \DateTimeImmutable('2024-03-15 09:00:00'));
        $leaveApplication->setEndDate(new \DateTimeImmutable('2024-03-17 18:00:00'));
        $leaveApplication->setDuration(3.0);
        $leaveApplication->setReason('年假休息');

        $repository = self::getService(LeaveApplicationRepository::class);
        self::assertInstanceOf(LeaveApplicationRepository::class, $repository);
        $repository->save($leaveApplication, true);

        // Verify leave application was created - use a unique combination to avoid fixture conflicts
        $savedApplication = self::getEntityManager()->getRepository(LeaveApplication::class)->findOneBy([
            'employeeId' => 999,
            'leaveType' => LeaveType::ANNUAL,
            'reason' => '年假休息',
            'duration' => 3.0,
        ]);
        $this->assertNotNull($savedApplication);
        $this->assertEquals(999, $savedApplication->getEmployeeId());
        $this->assertEquals(LeaveType::ANNUAL, $savedApplication->getLeaveType());
        $this->assertEquals(3.0, $savedApplication->getDuration());
        $this->assertEquals('年假休息', $savedApplication->getReason());
        $this->assertEquals(ApplicationStatus::PENDING, $savedApplication->getStatus());
    }

    public function testLeaveApplicationDataPersistence(): void
    {
        // Create client to initialize database
        $client = self::createClientWithDatabase();

        // Create test leave applications with different statuses
        $application1 = new LeaveApplication();
        $application1->setEmployeeId(1);
        $application1->setLeaveType(LeaveType::SICK);
        $application1->setStartDate(new \DateTimeImmutable('2024-03-20 09:00:00'));
        $application1->setEndDate(new \DateTimeImmutable('2024-03-22 18:00:00'));
        $application1->setDuration(3.0);
        $application1->setReason('感冒发烧');
        $application1->approve(1); // approverId

        $repository = self::getService(LeaveApplicationRepository::class);
        self::assertInstanceOf(LeaveApplicationRepository::class, $repository);
        $repository->save($application1, true);

        $application2 = new LeaveApplication();
        $application2->setEmployeeId(2);
        $application2->setLeaveType(LeaveType::PERSONAL);
        $application2->setStartDate(new \DateTimeImmutable('2024-03-25 09:00:00'));
        $application2->setEndDate(new \DateTimeImmutable('2024-03-25 18:00:00'));
        $application2->setDuration(1.0);
        $application2->setReason('个人事务');
        $application2->reject(2); // approverId

        $repository->save($application2, true);

        // Verify applications are saved correctly
        $savedApplication1 = $repository->findOneBy([
            'employeeId' => 1,
            'leaveType' => LeaveType::SICK,
        ]);
        $this->assertNotNull($savedApplication1);
        $this->assertEquals(1, $savedApplication1->getEmployeeId());
        $this->assertEquals(LeaveType::SICK, $savedApplication1->getLeaveType());
        $this->assertEquals(3.0, $savedApplication1->getDuration());
        $this->assertEquals(ApplicationStatus::APPROVED, $savedApplication1->getStatus());
        $this->assertEquals(1, $savedApplication1->getApproverId());
        $this->assertNotNull($savedApplication1->getApproveTime());

        $savedApplication2 = $repository->findOneBy([
            'employeeId' => 2,
            'leaveType' => LeaveType::PERSONAL,
        ]);
        $this->assertNotNull($savedApplication2);
        $this->assertEquals(2, $savedApplication2->getEmployeeId());
        $this->assertEquals(LeaveType::PERSONAL, $savedApplication2->getLeaveType());
        $this->assertEquals(ApplicationStatus::REJECTED, $savedApplication2->getStatus());
        $this->assertEquals(2, $savedApplication2->getApproverId());
        $this->assertNotNull($savedApplication2->getApproveTime());
    }

    public function testLeaveApplicationValidation(): void
    {
        $client = self::createClientWithDatabase();

        // Test validation constraints
        $repository = self::getService(LeaveApplicationRepository::class);
        self::assertInstanceOf(LeaveApplicationRepository::class, $repository);

        // Test valid leave application
        $validApplication = new LeaveApplication();
        $validApplication->setEmployeeId(301);
        $validApplication->setLeaveType(LeaveType::MATERNITY);
        $validApplication->setStartDate(new \DateTimeImmutable('2024-04-10 09:00:00'));
        $validApplication->setEndDate(new \DateTimeImmutable('2024-05-10 18:00:00'));
        $validApplication->setDuration(30.0);
        $validApplication->setReason('产假休息');
        $validApplication->approve(3);

        $repository->save($validApplication, true);
        $savedApplication = $repository->findOneBy(['employeeId' => 301, 'duration' => 30.0]);
        $this->assertNotNull($savedApplication);
        $this->assertEquals(30.0, $savedApplication->getDuration());
        $this->assertEquals('产假休息', $savedApplication->getReason());
    }

    public function testValidApplicationHasNoViolations(): void
    {
        self::bootKernel();
        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get(ValidatorInterface::class);

        $validApplication = new LeaveApplication();
        $validApplication->setEmployeeId(1);
        $validApplication->setLeaveType(LeaveType::ANNUAL);
        $validApplication->setStartDate(new \DateTimeImmutable('2024-03-15 09:00:00'));
        $validApplication->setEndDate(new \DateTimeImmutable('2024-03-17 18:00:00'));
        $validApplication->setDuration(3.0);
        $validApplication->setReason('年假休息');

        $violations = $validator->validate($validApplication);
        $this->assertCount(0, $violations, '有效申请不应有验证错误');
    }

    public function testEmployeeIdCannotBeInvalid(): void
    {
        self::bootKernel();
        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get(ValidatorInterface::class);

        $application = new LeaveApplication();
        $application->setEmployeeId(0); // Invalid employee ID
        $application->setLeaveType(LeaveType::ANNUAL);
        $application->setStartDate(new \DateTimeImmutable('2024-03-15 09:00:00'));
        $application->setEndDate(new \DateTimeImmutable('2024-03-17 18:00:00'));
        $application->setDuration(3.0);
        $application->setReason('年假休息');

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
        self::bootKernel();
        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get(ValidatorInterface::class);

        $application = new LeaveApplication();
        $application->setEmployeeId(1);
        $application->setLeaveType(LeaveType::ANNUAL);
        $application->setStartDate(new \DateTimeImmutable('2024-03-15 09:00:00'));
        $application->setEndDate(new \DateTimeImmutable('2024-03-17 18:00:00'));
        $application->setDuration(0); // Invalid duration
        $application->setReason('年假休息');

        $violations = $validator->validate($application);
        $this->assertGreaterThan(0, $violations->count(), '请假天数为0时应有验证错误');

        $hasDurationViolation = false;
        foreach ($violations as $violation) {
            if ('duration' === $violation->getPropertyPath()) {
                $hasDurationViolation = true;
                $this->assertStringContainsString('正数', (string) $violation->getMessage());
                break;
            }
        }
        $this->assertTrue($hasDurationViolation, '应包含请假天数验证错误');
    }

    public function testEndDateMustBeAfterStartDate(): void
    {
        self::bootKernel();
        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get(ValidatorInterface::class);

        $application = new LeaveApplication();
        $application->setEmployeeId(1);
        $application->setLeaveType(LeaveType::ANNUAL);
        $application->setStartDate(new \DateTimeImmutable('2024-03-17 09:00:00'));
        $application->setEndDate(new \DateTimeImmutable('2024-03-15 18:00:00')); // Before start date
        $application->setDuration(3.0);
        $application->setReason('年假休息');

        $violations = $validator->validate($application);
        $this->assertGreaterThan(0, $violations->count(), '结束日期在开始日期之前时应有验证错误');

        $hasEndDateViolation = false;
        foreach ($violations as $violation) {
            if ('endDate' === $violation->getPropertyPath()) {
                $hasEndDateViolation = true;
                $this->assertStringContainsString('必须晚于', (string) $violation->getMessage());
                break;
            }
        }
        $this->assertTrue($hasEndDateViolation, '应包含结束日期验证错误');
    }

    public function testReasonLengthValidation(): void
    {
        self::bootKernel();
        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get(ValidatorInterface::class);

        $application = new LeaveApplication();
        $application->setEmployeeId(1);
        $application->setLeaveType(LeaveType::ANNUAL);
        $application->setStartDate(new \DateTimeImmutable('2024-03-15 09:00:00'));
        $application->setEndDate(new \DateTimeImmutable('2024-03-17 18:00:00'));
        $application->setDuration(3.0);
        $application->setReason(str_repeat('这是一个很长的请假原因，', 100)); // > 1000 characters

        $violations = $validator->validate($application);
        $this->assertGreaterThan(0, $violations->count(), '请假原因超过最大长度时应有验证错误');

        $hasReasonViolation = false;
        foreach ($violations as $violation) {
            if ('reason' === $violation->getPropertyPath()) {
                $hasReasonViolation = true;
                $this->assertStringContainsString('1000', (string) $violation->getMessage());
                break;
            }
        }
        $this->assertTrue($hasReasonViolation, '应包含请假原因长度验证错误');
    }

    public function testDurationUpperLimitValidation(): void
    {
        self::bootKernel();
        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get(ValidatorInterface::class);

        $application = new LeaveApplication();
        $application->setEmployeeId(1);
        $application->setLeaveType(LeaveType::ANNUAL);
        $application->setStartDate(new \DateTimeImmutable('2024-01-01 09:00:00'));
        $application->setEndDate(new \DateTimeImmutable('2025-01-01 18:00:00'));
        $application->setDuration(366.0); // Exceeds maximum allowed days
        $application->setReason('超长假期');

        $violations = $validator->validate($application);
        $this->assertGreaterThan(0, $violations->count(), '请假天数超过365天时应有验证错误');

        $hasDurationViolation = false;
        foreach ($violations as $violation) {
            if ('duration' === $violation->getPropertyPath()) {
                $hasDurationViolation = true;
                $this->assertStringContainsString('365', (string) $violation->getMessage());
                break;
            }
        }
        $this->assertTrue($hasDurationViolation, '应包含请假天数上限验证错误');
    }

    public function testLeaveApplicationStatusTransitions(): void
    {
        // No database needed for this test

        // Create a pending application
        $application = new LeaveApplication();
        $application->setEmployeeId(1);
        $application->setLeaveType(LeaveType::ANNUAL);
        $application->setStartDate(new \DateTimeImmutable('2024-03-15 09:00:00'));
        $application->setEndDate(new \DateTimeImmutable('2024-03-17 18:00:00'));
        $application->setDuration(3.0);
        $application->setReason('年假休息');

        // Test approval
        $this->assertTrue($application->isPending());
        $this->assertTrue($application->canBeModified());
        $this->assertTrue($application->canBeCancelled());

        $application->approve(1);
        $this->assertTrue($application->isApproved());
        $this->assertFalse($application->canBeModified());
        $this->assertTrue($application->canBeCancelled());

        // Test rejection
        $application2 = new LeaveApplication();
        $application2->setEmployeeId(2);
        $application2->setLeaveType(LeaveType::SICK);
        $application2->setStartDate(new \DateTimeImmutable('2024-03-20 09:00:00'));
        $application2->setEndDate(new \DateTimeImmutable('2024-03-22 18:00:00'));
        $application2->setDuration(3.0);
        $application2->setReason('生病');

        $application2->reject(2);
        $this->assertTrue($application2->isRejected());
        $this->assertFalse($application2->canBeModified());
        $this->assertFalse($application2->canBeCancelled());

        // Test cancellation
        $application3 = new LeaveApplication();
        $application3->setEmployeeId(3);
        $application3->setLeaveType(LeaveType::PERSONAL);
        $application3->setStartDate(new \DateTimeImmutable('2024-03-25 09:00:00'));
        $application3->setEndDate(new \DateTimeImmutable('2024-03-25 18:00:00'));
        $application3->setDuration(1.0);
        $application3->setReason('个人事务');

        $application3->cancel();
        $this->assertTrue($application3->isCancelled());
        $this->assertFalse($application3->canBeModified());
        $this->assertFalse($application3->canBeCancelled());
    }

    public function testLeaveApplicationDateMethods(): void
    {
        // No database needed for this test

        $application = new LeaveApplication();
        $application->setEmployeeId(1);
        $application->setLeaveType(LeaveType::ANNUAL);
        $application->setStartDate(new \DateTimeImmutable('2024-03-15 09:00:00'));
        $application->setEndDate(new \DateTimeImmutable('2024-03-17 18:00:00'));
        $application->setDuration(3.0);
        $application->setReason('年假休息');

        // Test duration calculation
        $this->assertEquals(3, $application->getDurationInDays());

        // Test date range
        $dateRange = $application->getDateRange();
        $this->assertCount(3, $dateRange);
        $this->assertEquals('2024-03-15', $dateRange[0]->format('Y-m-d'));
        $this->assertEquals('2024-03-16', $dateRange[1]->format('Y-m-d'));
        $this->assertEquals('2024-03-17', $dateRange[2]->format('Y-m-d'));

        // Test overlap detection
        $this->assertTrue($application->isOverlapping(
            new \DateTimeImmutable('2024-03-16 09:00:00'),
            new \DateTimeImmutable('2024-03-18 18:00:00')
        ));

        $this->assertFalse($application->isOverlapping(
            new \DateTimeImmutable('2024-03-18 09:00:00'),
            new \DateTimeImmutable('2024-03-20 18:00:00')
        ));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'employeeId' => ['employeeId'];
        yield 'leaveType' => ['leaveType'];
        yield 'startDate' => ['startDate'];
        yield 'endDate' => ['endDate'];
        yield 'duration' => ['duration'];
        yield 'reason' => ['reason'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'employeeId' => ['employeeId'];
        yield 'leaveType' => ['leaveType'];
        yield 'startDate' => ['startDate'];
        yield 'endDate' => ['endDate'];
        yield 'duration' => ['duration'];
        yield 'reason' => ['reason'];
        yield 'status' => ['status'];
        yield 'approverId' => ['approverId'];
        yield 'approveTime' => ['approveTime'];
    }

    public function testApproveApplication(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create test entity first
        $repository = self::getService(LeaveApplicationRepository::class);
        self::assertInstanceOf(LeaveApplicationRepository::class, $repository);

        $entity = new LeaveApplication();
        $entity->setEmployeeId(1001);
        $entity->setLeaveType(LeaveType::ANNUAL);
        $entity->setStartDate(new \DateTimeImmutable('2024-06-10 09:00:00'));
        $entity->setEndDate(new \DateTimeImmutable('2024-06-12 18:00:00'));
        $entity->setDuration(3.0);
        $entity->setReason('测试批准动作');
        $repository->save($entity, true);

        // Test approve action
        $client->request('GET', sprintf('/admin/attendance/leave-applications/%d/approve', $entity->getId()));
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
        $repository = self::getService(LeaveApplicationRepository::class);
        self::assertInstanceOf(LeaveApplicationRepository::class, $repository);

        $entity = new LeaveApplication();
        $entity->setEmployeeId(1002);
        $entity->setLeaveType(LeaveType::SICK);
        $entity->setStartDate(new \DateTimeImmutable('2024-06-15 09:00:00'));
        $entity->setEndDate(new \DateTimeImmutable('2024-06-17 18:00:00'));
        $entity->setDuration(3.0);
        $entity->setReason('测试拒绝动作');
        $repository->save($entity, true);

        // Test reject action
        $client->request('GET', sprintf('/admin/attendance/leave-applications/%d/reject', $entity->getId()));
        $response = $client->getResponse();
        $this->assertTrue($response->isRedirection(), 'Response should be a redirect');

        // Verify the application status was updated
        $updatedEntity = $repository->find($entity->getId());
        $this->assertNotNull($updatedEntity);
        $this->assertTrue($updatedEntity->isRejected(), 'Application should be rejected');
    }

    public function testValidationErrors(): void
    {
        self::bootKernel();
        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get(ValidatorInterface::class);

        // Test invalid employee ID validation error - should not be blank
        $invalidApplication = new LeaveApplication();
        $invalidApplication->setEmployeeId(0); // Invalid employee ID
        $invalidApplication->setLeaveType(LeaveType::ANNUAL);
        $invalidApplication->setStartDate(new \DateTimeImmutable('2024-03-15 09:00:00'));
        $invalidApplication->setEndDate(new \DateTimeImmutable('2024-03-17 18:00:00'));
        $invalidApplication->setDuration(3.0);
        $invalidApplication->setReason('年假休息');

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
