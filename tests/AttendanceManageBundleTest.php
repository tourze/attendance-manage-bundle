<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AttendanceManageBundle\AttendanceManageBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(AttendanceManageBundle::class)]
#[RunTestsInSeparateProcesses]
final class AttendanceManageBundleTest extends AbstractBundleTestCase
{
}
