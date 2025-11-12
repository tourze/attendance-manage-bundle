<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\AttendanceManageBundle\Enum\ApplicationStatus;
use Tourze\AttendanceManageBundle\Enum\LeaveType;
use Tourze\AttendanceManageBundle\Repository\LeaveApplicationRepository;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: LeaveApplicationRepository::class)]
#[ORM\Table(name: 'leave_applications', options: ['comment' => '请假申请表'])]
#[ORM\Index(name: 'leave_applications_idx_date_range', columns: ['start_date', 'end_date'])]
class LeaveApplication implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private int $id;

    #[IndexColumn]
    #[ORM\Column(type: Types::INTEGER, name: 'employee_id', options: ['comment' => '员工ID'])]
    #[Assert\NotNull(message: '员工ID不能为空')]
    #[Assert\Positive(message: '员工ID必须为正数')]
    private int $employeeId;

    #[ORM\Column(type: Types::STRING, length: 20, name: 'leave_type', enumType: LeaveType::class, options: ['comment' => '请假类型'])]
    #[Assert\NotNull(message: '请假类型不能为空')]
    #[Assert\Choice(callback: [LeaveType::class, 'cases'], message: '无效的请假类型')]
    private LeaveType $leaveType;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'start_date', options: ['comment' => '请假开始日期'])]
    #[Assert\NotNull(message: '请假开始日期不能为空')]
    private \DateTimeImmutable $startDate;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'end_date', options: ['comment' => '请假结束日期'])]
    #[Assert\NotNull(message: '请假结束日期不能为空')]
    #[Assert\GreaterThan(propertyPath: 'startDate', message: '结束日期必须晚于开始日期')]
    private \DateTimeImmutable $endDate;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['comment' => '请假天数'])]
    #[Assert\NotNull(message: '请假天数不能为空')]
    #[Assert\Positive(message: '请假天数必须为正数')]
    #[Assert\LessThan(value: 366, message: '请假天数不能超过365天')]
    private float $duration;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '请假原因'])]
    #[Assert\Length(max: 1000, maxMessage: '请假原因不能超过1000个字符')]
    private ?string $reason;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 20, enumType: ApplicationStatus::class, options: ['comment' => '审批状态'])]
    #[Assert\NotNull(message: '审批状态不能为空')]
    #[Assert\Choice(callback: [ApplicationStatus::class, 'cases'], message: '无效的审批状态')]
    private ApplicationStatus $status;

    #[ORM\Column(type: Types::INTEGER, name: 'approver_id', nullable: true, options: ['comment' => '审批人ID'])]
    #[Assert\Positive(message: '审批人ID必须为正数')]
    private ?int $approverId;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'approve_time', nullable: true, options: ['comment' => '审批时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class, message: '审批时间格式不正确')]
    private ?\DateTimeImmutable $approveTime;

    public function __construct()
    {
        $this->status = ApplicationStatus::PENDING;
        $this->approverId = null;
        $this->approveTime = null;
        $this->createTime = new \DateTimeImmutable();
        $this->updateTime = new \DateTimeImmutable();
    }

    public function setEmployeeId(int $employeeId): void
    {
        $this->employeeId = $employeeId;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getLeaveType(): LeaveType
    {
        return $this->leaveType;
    }

    public function setLeaveType(LeaveType $leaveType): void
    {
        $this->leaveType = $leaveType;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): void
    {
        $this->startDate = $startDate;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): void
    {
        $this->endDate = $endDate;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function setDuration(float $duration): void
    {
        $this->duration = $duration;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): void
    {
        $this->reason = $reason;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getStatus(): ApplicationStatus
    {
        return $this->status;
    }

    public function setStatus(ApplicationStatus $status): void
    {
        $this->status = $status;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getApproverId(): ?int
    {
        return $this->approverId;
    }

    public function getApproveTime(): ?\DateTimeImmutable
    {
        return $this->approveTime;
    }

    public function approve(int $approverId): self
    {
        $this->status = ApplicationStatus::APPROVED;
        $this->approverId = $approverId;
        $this->approveTime = new \DateTimeImmutable();
        $this->updateTime = new \DateTimeImmutable();

        return $this;
    }

    public function reject(int $approverId): self
    {
        $this->status = ApplicationStatus::REJECTED;
        $this->approverId = $approverId;
        $this->approveTime = new \DateTimeImmutable();
        $this->updateTime = new \DateTimeImmutable();

        return $this;
    }

    public function cancel(): self
    {
        $this->status = ApplicationStatus::CANCELLED;
        $this->updateTime = new \DateTimeImmutable();

        return $this;
    }

    public function isPending(): bool
    {
        return ApplicationStatus::PENDING === $this->status;
    }

    public function isApproved(): bool
    {
        return ApplicationStatus::APPROVED === $this->status;
    }

    public function isRejected(): bool
    {
        return ApplicationStatus::REJECTED === $this->status;
    }

    public function isCancelled(): bool
    {
        return ApplicationStatus::CANCELLED === $this->status;
    }

    public function isProcessed(): bool
    {
        return !$this->isPending();
    }

    public function canBeModified(): bool
    {
        return $this->isPending();
    }

    public function canBeCancelled(): bool
    {
        return $this->isPending() || $this->isApproved();
    }

    public function getDurationInDays(): int
    {
        $diff = $this->endDate->diff($this->startDate);

        return (false !== $diff->days ? $diff->days : 0) + 1;
    }

    public function isOverlapping(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): bool
    {
        return !($this->endDate < $startDate || $this->startDate > $endDate);
    }

    /**
     * 获取请假日期范围内的所有日期
     *
     * @return array<\DateTimeImmutable>
     */
    public function getDateRange(): array
    {
        $dates = [];
        $current = $this->startDate;
        while ($current <= $this->endDate) {
            $dates[] = $current;
            $current = $current->modify('+1 day');
        }

        return $dates;
    }

    public function __toString(): string
    {
        $startStr = $this->startDate->format('Y-m-d');
        $endStr = $this->endDate->format('Y-m-d');

        return sprintf('员工%d %s请假申请 (%s至%s, %.1f天)', $this->employeeId, $this->leaveType->value, $startStr, $endStr, $this->duration);
    }
}
