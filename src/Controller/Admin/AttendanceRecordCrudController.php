<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Tourze\AttendanceManageBundle\Entity\AttendanceRecord;
use Tourze\AttendanceManageBundle\Enum\AttendanceStatus;
use Tourze\AttendanceManageBundle\Enum\CheckInType;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;

/**
 * @extends AbstractCrudController<AttendanceRecord>
 */
#[AdminCrud(routePath: '/attendance/records', routeName: 'attendance_records')]
final class AttendanceRecordCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AttendanceRecord::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('考勤记录')
            ->setEntityLabelInPlural('考勤记录')
            ->setPageTitle('index', '考勤记录管理')
            ->setPageTitle('new', '新建考勤记录')
            ->setPageTitle('edit', '编辑考勤记录')
            ->setPageTitle('detail', '考勤记录详情')
            ->setDefaultSort(['workDate' => 'DESC', 'employeeId' => 'ASC'])
            ->setPaginatorPageSize(50)
            ->setSearchFields(['employeeId', 'checkInLocation', 'checkOutLocation', 'abnormalReason'])
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
            ->add(NumericFilter::new('employeeId', '员工ID'))
            ->add(DateTimeFilter::new('workDate', '工作日期'))
            ->add('status')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnDetail()
        ;

        yield IntegerField::new('employeeId', '员工ID')
            ->setRequired(true)
            ->setHelp('员工的唯一标识')
        ;

        yield DateField::new('workDate', '工作日期')
            ->setRequired(true)
            ->setFormat('Y-m-d')
            ->setHelp('考勤日期')
        ;

        yield DateTimeField::new('checkInTime', '签到时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('实际签到时间')
        ;

        yield DateTimeField::new('checkOutTime', '签退时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('实际签退时间')
        ;

        $checkInTypeField = EnumField::new('checkInType', '签到类型');
        $checkInTypeField->setEnumCases(CheckInType::cases());
        $checkInTypeField->hideOnIndex();
        yield $checkInTypeField;

        yield TextField::new('checkInLocation', '签到位置')
            ->setMaxLength(200)
            ->hideOnIndex()
            ->setHelp('签到的地理位置信息')
        ;

        yield TextField::new('checkOutLocation', '签退位置')
            ->setMaxLength(200)
            ->hideOnIndex()
            ->setHelp('签退的地理位置信息')
        ;

        $statusField = EnumField::new('status', '考勤状态');
        $statusField->setEnumCases(AttendanceStatus::cases());
        $statusField->setRequired(true);
        yield $statusField;

        yield TextField::new('abnormalReason', '异常原因')
            ->setMaxLength(500)
            ->onlyOnForms()
            ->setHelp('考勤异常的原因描述')
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
