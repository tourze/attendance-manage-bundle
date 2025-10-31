<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Service;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\AttendanceManageBundle\Entity\AttendanceGroup;
use Tourze\AttendanceManageBundle\Entity\AttendanceRecord;
use Tourze\AttendanceManageBundle\Entity\HolidayConfig;
use Tourze\AttendanceManageBundle\Entity\LeaveApplication;
use Tourze\AttendanceManageBundle\Entity\OvertimeApplication;
use Tourze\AttendanceManageBundle\Entity\WorkShift;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(private LinkGeneratorInterface $linkGenerator)
    {
    }

    public function __invoke(ItemInterface $item): void
    {
        // 创建考勤管理顶级菜单
        if (null === $item->getChild('考勤管理')) {
            $item->addChild('考勤管理')
                ->setAttribute('icon', 'fa fa-clock')
            ;
        }

        $attendanceMenu = $item->getChild('考勤管理');
        if (null === $attendanceMenu) {
            return;
        }

        // 考勤记录管理
        $attendanceMenu
            ->addChild('考勤记录')
            ->setUri($this->linkGenerator->getCurdListPage(AttendanceRecord::class))
            ->setAttribute('icon', 'fa fa-calendar-check')
        ;

        // 考勤组管理
        $attendanceMenu
            ->addChild('考勤组管理')
            ->setUri($this->linkGenerator->getCurdListPage(AttendanceGroup::class))
            ->setAttribute('icon', 'fa fa-users')
        ;

        // 工作班次管理
        $attendanceMenu
            ->addChild('工作班次')
            ->setUri($this->linkGenerator->getCurdListPage(WorkShift::class))
            ->setAttribute('icon', 'fa fa-clock-o')
        ;

        // 请假管理
        $attendanceMenu
            ->addChild('请假管理')
            ->setUri($this->linkGenerator->getCurdListPage(LeaveApplication::class))
            ->setAttribute('icon', 'fa fa-calendar-minus-o')
        ;

        // 加班管理
        $attendanceMenu
            ->addChild('加班管理')
            ->setUri($this->linkGenerator->getCurdListPage(OvertimeApplication::class))
            ->setAttribute('icon', 'fa fa-clock-plus-o')
        ;

        // 节假日配置
        $attendanceMenu
            ->addChild('节假日配置')
            ->setUri($this->linkGenerator->getCurdListPage(HolidayConfig::class))
            ->setAttribute('icon', 'fa fa-calendar')
        ;
    }
}
