<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Tourze\AttendanceManageBundle\Entity\WorkShift;

/**
 * @extends AbstractCrudController<WorkShift>
 */
#[AdminCrud(routePath: '/attendance/work-shifts', routeName: 'attendance_work_shifts')]
final class WorkShiftCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return WorkShift::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('工作班次')
            ->setEntityLabelInPlural('工作班次')
            ->setPageTitle('index', '工作班次管理')
            ->setPageTitle('new', '新建工作班次')
            ->setPageTitle('edit', '编辑工作班次')
            ->setPageTitle('detail', '工作班次详情')
            ->setDefaultSort(['groupId' => 'ASC', 'startTime' => 'ASC'])
            ->setPaginatorPageSize(30)
            ->setSearchFields(['name', 'groupId'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(NumericFilter::new('groupId', '考勤组ID'))
            ->add('crossDay')
            ->add('isActive')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnDetail()
        ;

        yield IntegerField::new('groupId', '考勤组ID')
            ->setRequired(true)
            ->setHelp('所属考勤组的ID')
        ;

        yield TextField::new('name', '班次名称')
            ->setRequired(true)
            ->setMaxLength(100)
            ->setHelp('班次名称，最多100个字符')
        ;

        yield TimeField::new('startTime', '开始时间')
            ->setRequired(true)
            ->setFormat('H:i')
            ->setHelp('班次开始时间')
        ;

        yield TimeField::new('endTime', '结束时间')
            ->setRequired(true)
            ->setFormat('H:i')
            ->setHelp('班次结束时间')
        ;

        yield IntegerField::new('flexibleMinutes', '弹性时间(分钟)')
            ->setHelp('允许的弹性打卡时间，单位：分钟')
            ->hideOnIndex()
        ;

        yield ArrayField::new('breakTimes', '休息时间配置')
            ->setHelp('休息时间段配置，格式：[{"start":"12:00","end":"13:00"}]')
            ->hideOnIndex()
            ->formatValue($this->formatBreakTimesValue(...))
        ;

        yield BooleanField::new('crossDay', '是否跨天班次')
            ->renderAsSwitch(false)
            ->setHelp('是否为跨天班次（如夜班）')
        ;

        yield BooleanField::new('isActive', '启用状态')
            ->setRequired(true)
            ->renderAsSwitch(false)
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->onlyOnDetail()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }

    /**
     * 格式化休息时间配置的值
     */
    private function formatBreakTimesValue(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $formatted = [];
        foreach ($value as $breakTime) {
            if ($this->isValidBreakTime($breakTime)) {
                /** @var array{start: string, end: string} $breakTime */
                $formatted[] = $breakTime['start'] . '-' . $breakTime['end'];
            }
        }

        return implode(', ', $formatted);
    }

    /**
     * 验证休息时间配置是否有效
     */
    private function isValidBreakTime(mixed $breakTime): bool
    {
        return is_array($breakTime)
            && isset($breakTime['start'], $breakTime['end'])
            && is_string($breakTime['start'])
            && is_string($breakTime['end']);
    }
}
