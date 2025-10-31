<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AttendanceManageBundle\DependencyInjection\AttendanceManageExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(AttendanceManageExtension::class)]
final class AttendanceManageExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Extension 测试不需要特殊的设置
    }
}
