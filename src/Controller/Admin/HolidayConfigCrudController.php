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
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Tourze\AttendanceManageBundle\Entity\HolidayConfig;

/**
 * @extends AbstractCrudController<HolidayConfig>
 */
#[AdminCrud(routePath: '/attendance/holiday-configs', routeName: 'attendance_holiday_configs')]
final class HolidayConfigCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return HolidayConfig::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('节假日配置')
            ->setEntityLabelInPlural('节假日配置')
            ->setPageTitle('index', '节假日配置管理')
            ->setPageTitle('new', '新建节假日配置')
            ->setPageTitle('edit', '编辑节假日配置')
            ->setPageTitle('detail', '节假日配置详情')
            ->setDefaultSort(['holidayDate' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->setSearchFields(['name', 'type', 'description'])
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
            ->add(DateTimeFilter::new('holidayDate', '节假日日期'))
            ->add('type')
            ->add('isPaid')
            ->add('isMandatory')
            ->add('isActive')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnDetail()
        ;

        yield TextField::new('name', '节假日名称')
            ->setRequired(true)
            ->setMaxLength(100)
            ->setHelp('节假日的名称，最多100个字符')
        ;

        yield DateField::new('holidayDate', '节假日日期')
            ->setRequired(true)
            ->setFormat('Y-m-d')
            ->setHelp('节假日的具体日期')
        ;

        yield ChoiceField::new('type', '节假日类型')
            ->setRequired(true)
            ->setChoices([
                '国家法定节假日' => HolidayConfig::TYPE_NATIONAL,
                '公司内部假期' => HolidayConfig::TYPE_COMPANY,
                '特殊假期' => HolidayConfig::TYPE_SPECIAL,
            ])
            ->setHelp('选择节假日的类型')
            ->renderAsBadges([
                HolidayConfig::TYPE_NATIONAL => 'danger',
                HolidayConfig::TYPE_COMPANY => 'primary',
                HolidayConfig::TYPE_SPECIAL => 'warning',
            ])
        ;

        yield TextareaField::new('description', '节假日描述')
            ->setMaxLength(1000)
            ->hideOnIndex()
            ->setHelp('节假日的详细描述，最多1000个字符')
        ;

        yield BooleanField::new('isPaid', '是否带薪')
            ->setRequired(true)
            ->renderAsSwitch(false)
            ->setHelp('是否为带薪假期')
        ;

        yield BooleanField::new('isMandatory', '是否强制休假')
            ->setRequired(true)
            ->renderAsSwitch(false)
            ->setHelp('是否强制员工休假')
        ;

        yield ArrayField::new('applicableDepartments', '适用部门')
            ->setHelp('适用的部门ID列表，为空表示所有部门')
            ->hideOnIndex()
        ;

        yield BooleanField::new('isActive', '启用状态')
            ->setRequired(true)
            ->renderAsSwitch(false)
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->onlyOnDetail()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->onlyOnDetail()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }
}
