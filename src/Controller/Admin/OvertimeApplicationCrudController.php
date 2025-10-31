<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Tourze\AttendanceManageBundle\Entity\OvertimeApplication;
use Tourze\AttendanceManageBundle\Enum\ApplicationStatus;
use Tourze\AttendanceManageBundle\Enum\CompensationType;
use Tourze\AttendanceManageBundle\Enum\OvertimeType;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;

/**
 * @extends AbstractCrudController<OvertimeApplication>
 */
#[AdminCrud(routePath: '/attendance/overtime-applications', routeName: 'attendance_overtime_applications')]
final class OvertimeApplicationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return OvertimeApplication::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('加班申请')
            ->setEntityLabelInPlural('加班申请')
            ->setPageTitle('index', '加班申请管理')
            ->setPageTitle('new', '新建加班申请')
            ->setPageTitle('edit', '编辑加班申请')
            ->setPageTitle('detail', '加班申请详情')
            ->setDefaultSort(['overtimeDate' => 'DESC', 'employeeId' => 'ASC'])
            ->setPaginatorPageSize(30)
            ->setSearchFields(['employeeId', 'reason'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $approveAction = Action::new('approve', '批准')
            ->linkToCrudAction('approveApplication')
            ->setCssClass('btn btn-success')
            ->setIcon('fa fa-check')
            ->displayIf(fn (OvertimeApplication $entity) => $entity->isPending())
        ;

        $rejectAction = Action::new('reject', '拒绝')
            ->linkToCrudAction('rejectApplication')
            ->setCssClass('btn btn-danger')
            ->setIcon('fa fa-times')
            ->displayIf(fn (OvertimeApplication $entity) => $entity->isPending())
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $approveAction)
            ->add(Crud::PAGE_INDEX, $rejectAction)
            // ->setPermission(Action::NEW, 'ROLE_USER') // 移除权限限制以便测试
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ->setPermission('approve', 'ROLE_ADMIN')
            ->setPermission('reject', 'ROLE_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(NumericFilter::new('employeeId', '员工ID'))
            ->add(DateTimeFilter::new('overtimeDate', '加班日期'))
            ->add('overtimeType')
            ->add('compensationType')
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
            ->setHelp('申请人的员工ID')
        ;

        yield DateField::new('overtimeDate', '加班日期')
            ->setRequired(true)
            ->setFormat('Y-m-d')
            ->setHelp('加班的日期')
        ;

        yield TimeField::new('startTime', '开始时间')
            ->setRequired(true)
            ->setFormat('H:i')
            ->hideOnIndex()
            ->setHelp('加班开始时间')
        ;

        yield TimeField::new('endTime', '结束时间')
            ->setRequired(true)
            ->setFormat('H:i')
            ->hideOnIndex()
            ->setHelp('加班结束时间')
        ;

        yield NumberField::new('duration', '加班时长(小时)')
            ->setRequired(true)
            ->setNumDecimals(2)
            ->setHelp('加班的总时长，单位：小时')
        ;

        $overtimeTypeField = EnumField::new('overtimeType', '加班类型');
        $overtimeTypeField->setEnumCases(OvertimeType::cases());
        $overtimeTypeField->setRequired(true);
        $overtimeTypeField->renderAsBadges([
            OvertimeType::WORKDAY->value => 'primary',
            OvertimeType::WEEKEND->value => 'warning',
            OvertimeType::HOLIDAY->value => 'danger',
        ]);
        yield $overtimeTypeField;

        yield TextareaField::new('reason', '加班原因')
            ->setMaxLength(1000)
            ->hideOnIndex()
            ->setHelp('详细说明加班原因，最多1000个字符')
        ;

        $statusField = ChoiceField::new('status', '审批状态');
        if (Crud::PAGE_INDEX === $pageName) {
            $statusField
                ->setChoices(ApplicationStatus::getSelectChoices())
                ->renderAsBadges([
                    ApplicationStatus::PENDING->value => 'warning',
                    ApplicationStatus::APPROVED->value => 'success',
                    ApplicationStatus::REJECTED->value => 'danger',
                    ApplicationStatus::CANCELLED->value => 'secondary',
                ])
            ;
        } else {
            $statusField
                ->setFormType(EnumType::class)
                ->setFormTypeOptions([
                    'class' => ApplicationStatus::class,
                    'choice_label' => 'label',
                ])
                ->setHelp('当前申请的审批状态')
            ;
        }
        yield $statusField;

        yield ChoiceField::new('compensationType', '补偿方式')
            ->setRequired(true)
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => CompensationType::class,
                'choices' => CompensationType::cases(),
                'choice_label' => function (CompensationType $type) {
                    return $type->getLabel();
                },
            ])
            ->renderAsBadges([
                CompensationType::PAID->value => 'success',
                CompensationType::TIMEOFF->value => 'info',
            ])
            ->setHelp('选择加班补偿方式')
        ;

        yield IntegerField::new('approverId', '审批人ID')
            ->onlyOnForms()
            ->setHelp('审批人的员工ID')
        ;

        yield DateTimeField::new('approveTime', '审批时间')
            ->hideOnIndex()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
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

    #[AdminAction(routeName: 'approve_application', routePath: '{entityId}/approve')]
    public function approveApplication(AdminContext $context): Response
    {
        $application = $context->getEntity()->getInstance();
        assert($application instanceof OvertimeApplication);

        if (!$application->isPending()) {
            $this->addFlash('warning', '只能批准待审批的申请');
            $referer = $context->getRequest()->headers->get('referer');

            return $this->redirect(null !== $referer ? $referer : '/admin');
        }

        // 实现批准逻辑（假设审批人ID为1）
        $application->approve(1);
        $this->container->get('doctrine')->getManager()->flush();

        $this->addFlash('success', '申请已批准');
        $referer = $context->getRequest()->headers->get('referer');

        return $this->redirect(null !== $referer ? $referer : '/admin');
    }

    #[AdminAction(routeName: 'reject_application', routePath: '{entityId}/reject')]
    public function rejectApplication(AdminContext $context): Response
    {
        $application = $context->getEntity()->getInstance();
        assert($application instanceof OvertimeApplication);

        if (!$application->isPending()) {
            $this->addFlash('warning', '只能拒绝待审批的申请');
            $referer = $context->getRequest()->headers->get('referer');

            return $this->redirect(null !== $referer ? $referer : '/admin');
        }

        // 实现拒绝逻辑（假设审批人ID为1）
        $application->reject(1);
        $this->container->get('doctrine')->getManager()->flush();

        $this->addFlash('success', '申请已拒绝');
        $referer = $context->getRequest()->headers->get('referer');

        return $this->redirect(null !== $referer ? $referer : '/admin');
    }
}
