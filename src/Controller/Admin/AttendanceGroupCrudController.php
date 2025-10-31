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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Tourze\AttendanceManageBundle\Entity\AttendanceGroup;
use Tourze\AttendanceManageBundle\Enum\AttendanceGroupType;

/**
 * @extends AbstractCrudController<AttendanceGroup>
 */
#[AdminCrud(routePath: '/attendance/groups', routeName: 'attendance_groups')]
final class AttendanceGroupCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AttendanceGroup::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('考勤组')
            ->setEntityLabelInPlural('考勤组')
            ->setPageTitle('index', '考勤组管理')
            ->setPageTitle('new', '新建考勤组')
            ->setPageTitle('edit', '编辑考勤组')
            ->setPageTitle('detail', '考勤组详情')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->setSearchFields(['name', 'type'])
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
            ->add('type')
            ->add('isActive')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnDetail()
        ;

        yield TextField::new('name', '考勤组名称')
            ->setRequired(true)
            ->setMaxLength(100)
            ->setHelp('考勤组的名称，最多100个字符')
        ;

        yield ChoiceField::new('type', '考勤组类型')
            ->setRequired(true)
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => AttendanceGroupType::class,
                'choices' => AttendanceGroupType::cases(),
                'choice_label' => function (AttendanceGroupType $type) {
                    return $type->getLabel();
                },
            ])
            ->setHelp('选择考勤组的类型')
            ->renderAsBadges([
                'fixed' => 'primary',
                'flexible' => 'success',
                'shift' => 'warning',
            ])
        ;

        yield ArrayField::new('rules', '考勤规则')
            ->setRequired(true)
            ->setHelp('考勤规则配置')
            ->hideOnIndex()
            ->formatValue(function ($value) {
                if (!is_array($value)) {
                    return $value;
                }

                return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            })
        ;

        yield ArrayField::new('memberIds', '成员ID列表')
            ->setRequired(true)
            ->setHelp('考勤组成员的用户ID列表')
            ->hideOnIndex()
        ;

        yield BooleanField::new('isActive', '启用状态')
            ->setRequired(true)
            ->renderAsSwitch(false)
        ;

        yield IntegerField::new('version', '版本号')
            ->onlyOnDetail()
            ->setHelp('用于乐观锁控制')
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
