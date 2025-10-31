<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\AttendanceManageBundle\Entity\AttendanceRecord;
use Tourze\AttendanceManageBundle\Enum\AttendanceStatus;
use Tourze\AttendanceManageBundle\Service\AttendanceStatusCalculator;
use Tourze\AttendanceManageBundle\Service\RuleService;

/**
 * @internal
 */
#[CoversClass(AttendanceStatusCalculator::class)]
class AttendanceStatusCalculatorTest extends TestCase
{
    private AttendanceStatusCalculator $calculator;

    /** @var MockObject&RuleService */
    private MockObject $ruleService;

    protected function setUp(): void
    {
        // 创建 RuleService mock
        /** @var MockObject&RuleService $ruleService */
        $ruleService = $this->createMock(RuleService::class);
        $this->ruleService = $ruleService;

        // 创建真实的 AttendanceStatusCalculator 实例，注入 Mock 的依赖
        $this->calculator = new AttendanceStatusCalculator($this->ruleService);
    }

    public function testCalculateCheckInStatusWithNoShifts(): void
    {
        $this->ruleService->method('getApplicableRules')
            ->willReturn(['shifts' => []])
        ;

        $status = $this->calculator->calculateCheckInStatus(1, new \DateTimeImmutable());

        $this->assertSame(AttendanceStatus::NORMAL, $status);
    }

    public function testCalculateCheckOutStatusWithLateRecord(): void
    {
        /** @var MockObject&AttendanceRecord $record */
        $record = $this->createMock(AttendanceRecord::class);
        $record->method('isLate')->willReturn(true);
        $record->method('getEmployeeId')->willReturn(1);

        $status = $this->calculator->calculateCheckOutStatus($record, new \DateTimeImmutable());

        $this->assertSame(AttendanceStatus::LATE, $status);
    }

    public function testCalculateCheckInStatusWithException(): void
    {
        $this->ruleService->method('getApplicableRules')
            ->willThrowException(new \Exception('Test exception'))
        ;

        $status = $this->calculator->calculateCheckInStatus(1, new \DateTimeImmutable());

        $this->assertSame(AttendanceStatus::NORMAL, $status);
    }

    public function testCalculateCheckOutStatusWithException(): void
    {
        /** @var MockObject&AttendanceRecord $record */
        $record = $this->createMock(AttendanceRecord::class);
        $record->method('isLate')->willReturn(false);
        $record->method('getEmployeeId')->willReturn(1);

        $this->ruleService->method('getApplicableRules')
            ->willThrowException(new \Exception('Test exception'))
        ;

        $status = $this->calculator->calculateCheckOutStatus($record, new \DateTimeImmutable());

        $this->assertSame(AttendanceStatus::NORMAL, $status);
    }
}
