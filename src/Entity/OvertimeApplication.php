<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\AttendanceManageBundle\Enum\ApplicationStatus;
use Tourze\AttendanceManageBundle\Enum\CompensationType;
use Tourze\AttendanceManageBundle\Enum\OvertimeType;
use Tourze\AttendanceManageBundle\Repository\OvertimeApplicationRepository;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: OvertimeApplicationRepository::class)]
#[ORM\Table(name: 'overtime_applications', options: ['comment' => '加班申请表'])]
class OvertimeApplication implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private int $id; // @phpstan-ignore-line

    #[IndexColumn]
    #[ORM\Column(type: Types::INTEGER, name: 'employee_id', options: ['comment' => '员工ID'])]
    #[Assert\NotNull(message: '员工ID不能为空')]
    #[Assert\Positive(message: '员工ID必须为正数')]
    private int $employeeId;

    #[IndexColumn]
    #[ORM\Column(type: Types::DATE_IMMUTABLE, name: 'overtime_date', options: ['comment' => '加班日期'])]
    #[Assert\NotNull(message: '加班日期不能为空')]
    private \DateTimeImmutable $overtimeDate;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'start_time', options: ['comment' => '加班开始时间'])]
    #[Assert\NotNull(message: '加班开始时间不能为空')]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'end_time', options: ['comment' => '加班结束时间'])]
    #[Assert\NotNull(message: '加班结束时间不能为空')]
    #[Assert\GreaterThan(propertyPath: 'startTime', message: '结束时间必须晚于开始时间')]
    private \DateTimeImmutable $endTime;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['comment' => '加班时长(小时)'])]
    #[Assert\NotNull(message: '加班时长不能为空')]
    #[Assert\Positive(message: '加班时长必须为正数')]
    #[Assert\LessThan(value: 24, message: '加班时长不能超过24小时')]
    private float $duration;

    #[ORM\Column(type: Types::STRING, length: 20, name: 'overtime_type', enumType: OvertimeType::class, options: ['comment' => '加班类型'])]
    #[Assert\NotNull(message: '加班类型不能为空')]
    #[Assert\Choice(callback: [OvertimeType::class, 'cases'], message: '无效的加班类型')]
    private OvertimeType $overtimeType;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '加班原因'])]
    #[Assert\Length(max: 1000, maxMessage: '加班原因不能超过1000个字符')]
    private ?string $reason;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 20, enumType: ApplicationStatus::class, options: ['comment' => '审批状态'])]
    #[Assert\NotNull(message: '审批状态不能为空')]
    #[Assert\Choice(callback: [ApplicationStatus::class, 'cases'], message: '无效的审批状态')]
    private ApplicationStatus $status;

    #[ORM\Column(type: Types::STRING, length: 20, name: 'compensation_type', enumType: CompensationType::class, options: ['comment' => '补偿方式'])]
    #[Assert\NotNull(message: '补偿方式不能为空')]
    #[Assert\Choice(callback: [CompensationType::class, 'cases'], message: '无效的补偿方式')]
    private CompensationType $compensationType;

    #[ORM\Column(type: Types::INTEGER, name: 'approver_id', nullable: true, options: ['comment' => '审批人ID'])]
    #[Assert\Positive(message: '审批人ID必须为正数')]
    private ?int $approverId;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'approve_time', nullable: true, options: ['comment' => '审批时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class, message: '审批时间格式不正确')]
    private ?\DateTimeImmutable $approveTime;

    public function __construct()
    {
        $this->status = ApplicationStatus::PENDING;
        $this->compensationType = CompensationType::PAID;
        $this->approverId = null;
        $this->approveTime = null;
        $this->createTime = new \DateTimeImmutable();
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

    public function setEmployeeId(int $employeeId): void
    {
        $this->employeeId = $employeeId;
    }

    public function getOvertimeDate(): \DateTimeImmutable
    {
        return $this->overtimeDate;
    }

    public function setOvertimeDate(\DateTimeImmutable $overtimeDate): void
    {
        $this->overtimeDate = $overtimeDate;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getStartTime(): \DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): void
    {
        $this->startTime = $startTime;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getEndTime(): \DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeImmutable $endTime): void
    {
        $this->endTime = $endTime;
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

    public function getOvertimeType(): OvertimeType
    {
        return $this->overtimeType;
    }

    public function setOvertimeType(OvertimeType $overtimeType): void
    {
        $this->overtimeType = $overtimeType;
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

    public function getCompensationType(): CompensationType
    {
        return $this->compensationType;
    }

    public function setCompensationType(CompensationType $compensationType): void
    {
        $this->compensationType = $compensationType;
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

    public function isWorkdayOvertime(): bool
    {
        return OvertimeType::WORKDAY === $this->overtimeType;
    }

    public function isWeekendOvertime(): bool
    {
        return OvertimeType::WEEKEND === $this->overtimeType;
    }

    public function isHolidayOvertime(): bool
    {
        return OvertimeType::HOLIDAY === $this->overtimeType;
    }

    public function isPaidCompensation(): bool
    {
        return CompensationType::PAID === $this->compensationType;
    }

    public function isTimeoffCompensation(): bool
    {
        return CompensationType::TIMEOFF === $this->compensationType;
    }

    public function getOvertimeMultiplier(): float
    {
        return $this->overtimeType->getMultiplier();
    }

    public function getCompensationHours(): float
    {
        if ($this->isTimeoffCompensation()) {
            return $this->duration * $this->getOvertimeMultiplier();
        }

        return $this->duration;
    }

    public function canBeModified(): bool
    {
        return $this->isPending();
    }

    public function canBeCancelled(): bool
    {
        return $this->isPending() || $this->isApproved();
    }

    public function __toString(): string
    {
        $dateStr = $this->overtimeDate->format('Y-m-d');
        $timeStr = sprintf('%s-%s', $this->startTime->format('H:i'), $this->endTime->format('H:i'));

        return sprintf('员工%d %s加班申请 (%s, %.1f小时)', $this->employeeId, $dateStr, $timeStr, $this->duration);
    }
}
