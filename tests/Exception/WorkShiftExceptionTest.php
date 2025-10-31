<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AttendanceManageBundle\Exception\AttendanceException;
use Tourze\AttendanceManageBundle\Exception\WorkShiftException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(WorkShiftException::class)]
class WorkShiftExceptionTest extends AbstractExceptionTestCase
{
    public function testExtendsAttendanceException(): void
    {
        $exception = WorkShiftException::invalidTimeFormat('test');
        $this->assertInstanceOf(AttendanceException::class, $exception);
    }

    public function testInvalidTimeFormat(): void
    {
        $timeString = 'invalid_time_format';
        $exception = WorkShiftException::invalidTimeFormat($timeString);

        $this->assertInstanceOf(WorkShiftException::class, $exception);
        $this->assertEquals("无效的时间格式: {$timeString}", $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testTimeCreationFailed(): void
    {
        $timeString = '25:99';
        $exception = WorkShiftException::timeCreationFailed($timeString);

        $this->assertInstanceOf(WorkShiftException::class, $exception);
        $this->assertEquals("时间创建失败: {$timeString}", $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testInvalidTimeFormatWithEmptyString(): void
    {
        $timeString = '';
        $exception = WorkShiftException::invalidTimeFormat($timeString);

        $this->assertEquals('无效的时间格式: ', $exception->getMessage());
    }

    public function testTimeCreationFailedWithEmptyString(): void
    {
        $timeString = '';
        $exception = WorkShiftException::timeCreationFailed($timeString);

        $this->assertEquals('时间创建失败: ', $exception->getMessage());
    }

    public function testInvalidTimeFormatWithComplexString(): void
    {
        $timeString = '12:30:45.123 UTC+8';
        $exception = WorkShiftException::invalidTimeFormat($timeString);

        $this->assertEquals("无效的时间格式: {$timeString}", $exception->getMessage());
    }

    public function testTimeCreationFailedWithComplexString(): void
    {
        $timeString = '2024-01-01 25:99:99';
        $exception = WorkShiftException::timeCreationFailed($timeString);

        $this->assertEquals("时间创建失败: {$timeString}", $exception->getMessage());
    }

    public function testExceptionIsThrowable(): void
    {
        $exception = WorkShiftException::invalidTimeFormat('test');

        $this->assertInstanceOf(\Throwable::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testFactoryMethodsReturnSameClass(): void
    {
        $exception1 = WorkShiftException::invalidTimeFormat('test');
        $exception2 = WorkShiftException::timeCreationFailed('test');

        $this->assertSame(WorkShiftException::class, get_class($exception1));
        $this->assertSame(WorkShiftException::class, get_class($exception2));
    }
}
