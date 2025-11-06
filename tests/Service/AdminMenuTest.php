<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Tests\Service;

use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AttendanceManageBundle\Entity\AttendanceGroup;
use Tourze\AttendanceManageBundle\Entity\AttendanceRecord;
use Tourze\AttendanceManageBundle\Entity\HolidayConfig;
use Tourze\AttendanceManageBundle\Entity\LeaveApplication;
use Tourze\AttendanceManageBundle\Entity\OvertimeApplication;
use Tourze\AttendanceManageBundle\Entity\WorkShift;
use Tourze\AttendanceManageBundle\Service\AdminMenu;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private AdminMenu $adminMenu;

    private LinkGeneratorInterface $linkGenerator;

    protected function onSetUp(): void
    {
        $this->linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        $this->adminMenu = self::getService(AdminMenu::class);
    }

    public function testInvokeCreatesAttendanceManagementMenu(): void
    {
        $factory = new MenuFactory();
        $rootItem = $factory->createItem('root');

        $this->linkGenerator
            ->method('getCurdListPage')
            ->willReturnCallback(function (string $entityClass) {
                return '/admin?crudAction=index&crudControllerFqcn=' . urlencode($entityClass);
            })
        ;

        ($this->adminMenu)($rootItem);

        // 验证顶级菜单存在
        $attendanceMenu = $rootItem->getChild('考勤管理');
        $this->assertNotNull($attendanceMenu);
        $this->assertEquals('fa fa-clock', $attendanceMenu->getAttribute('icon'));

        // 验证所有子菜单项存在
        $expectedMenus = [
            '考勤记录' => [
                'icon' => 'fa fa-calendar-check',
                'uri' => '/admin?crudAction=index&crudControllerFqcn=' . urlencode(AttendanceRecord::class),
            ],
            '考勤组管理' => [
                'icon' => 'fa fa-users',
                'uri' => '/admin?crudAction=index&crudControllerFqcn=' . urlencode(AttendanceGroup::class),
            ],
            '工作班次' => [
                'icon' => 'fa fa-clock-o',
                'uri' => '/admin?crudAction=index&crudControllerFqcn=' . urlencode(WorkShift::class),
            ],
            '请假管理' => [
                'icon' => 'fa fa-calendar-minus-o',
                'uri' => '/admin?crudAction=index&crudControllerFqcn=' . urlencode(LeaveApplication::class),
            ],
            '加班管理' => [
                'icon' => 'fa fa-clock-plus-o',
                'uri' => '/admin?crudAction=index&crudControllerFqcn=' . urlencode(OvertimeApplication::class),
            ],
            '节假日配置' => [
                'icon' => 'fa fa-calendar',
                'uri' => '/admin?crudAction=index&crudControllerFqcn=' . urlencode(HolidayConfig::class),
            ],
        ];

        foreach ($expectedMenus as $menuName => $config) {
            $menuItem = $attendanceMenu->getChild($menuName);
            $this->assertNotNull($menuItem, "菜单项 '{$menuName}' 应该存在");
            $this->assertEquals($config['icon'], $menuItem->getAttribute('icon'));
            $this->assertEquals($config['uri'], $menuItem->getUri());
        }
    }

    public function testInvokeWithExistingAttendanceManagementMenu(): void
    {
        $factory = new MenuFactory();
        $rootItem = $factory->createItem('root');
        $rootItem->addChild('考勤管理')->setAttribute('icon', 'existing-icon');

        $this->linkGenerator
            ->method('getCurdListPage')
            ->willReturn('/admin/test')
        ;

        ($this->adminMenu)($rootItem);

        // 验证现有菜单不会被重复创建
        $attendanceMenu = $rootItem->getChild('考勤管理');
        $this->assertNotNull($attendanceMenu);
        $this->assertEquals('existing-icon', $attendanceMenu->getAttribute('icon'));

        // 但子菜单应该被添加
        $this->assertNotNull($attendanceMenu->getChild('考勤记录'));
    }

    public function testInvokeHandlesNullAttendanceMenu(): void
    {
        $rootItem = $this->createMock(MenuItem::class);
        $rootItem->method('getChild')
            ->with('考勤管理')
            ->willReturn(null)
        ;

        $rootItem->expects($this->once())
            ->method('addChild')
            ->with('考勤管理')
            ->willReturn($rootItem)
        ;

        // 确保当 getChild 返回 null 时，方法能正常处理
        ($this->adminMenu)($rootItem);

        // 如果没有抛出异常，测试通过
        $this->assertTrue(true);
    }
}
