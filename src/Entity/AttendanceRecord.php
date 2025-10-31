<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\AttendanceManageBundle\Enum\AttendanceStatus;
use Tourze\AttendanceManageBundle\Enum\CheckInType;
use Tourze\AttendanceManageBundle\Repository\AttendanceRecordRepository;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: AttendanceRecordRepository::class)]
#[ORM\Table(name: 'attendance_records', options: ['comment' => '考勤记录表'])]
#[ORM\UniqueConstraint(name: 'uk_employee_date', columns: ['employee_id', 'work_date'])]
class AttendanceRecord implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '主键ID'])]
    private int $id; // @phpstan-ignore-line

    #[IndexColumn]
    #[ORM\Column(type: Types::INTEGER, name: 'employee_id', options: ['comment' => '员工ID'])]
    #[Assert\Positive(message: '员工ID必须大于0')]
    #[Assert\NotNull(message: '员工ID不能为空')]
    private int $employeeId;

    #[IndexColumn]
    #[ORM\Column(type: Types::DATE_IMMUTABLE, name: 'work_date', options: ['comment' => '工作日期'])]
    #[Assert\NotNull(message: '工作日期不能为空')]
    private \DateTimeImmutable $workDate;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'check_in_time', nullable: true, options: ['comment' => '签到时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class, message: '签到时间格式不正确')]
    private ?\DateTimeImmutable $checkInTime;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'check_out_time', nullable: true, options: ['comment' => '签退时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class, message: '签退时间格式不正确')]
    private ?\DateTimeImmutable $checkOutTime;

    #[ORM\Column(type: Types::STRING, length: 20, name: 'check_in_type', nullable: true, enumType: CheckInType::class, options: ['comment' => '签到类型'])]
    #[Assert\Choice(callback: [CheckInType::class, 'cases'], message: '无效的签到类型')]
    private ?CheckInType $checkInType;

    #[ORM\Column(type: Types::STRING, length: 200, name: 'check_in_location', nullable: true, options: ['comment' => '签到位置'])]
    #[Assert\Length(max: 200, maxMessage: '签到位置不能超过200个字符')]
    private ?string $checkInLocation;

    #[ORM\Column(type: Types::STRING, length: 200, name: 'check_out_location', nullable: true, options: ['comment' => '签退位置'])]
    #[Assert\Length(max: 200, maxMessage: '签退位置不能超过200个字符')]
    private ?string $checkOutLocation;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 20, enumType: AttendanceStatus::class, options: ['comment' => '考勤状态'])]
    #[Assert\NotNull(message: '考勤状态不能为空')]
    #[Assert\Choice(callback: [AttendanceStatus::class, 'cases'], message: '无效的考勤状态')]
    private AttendanceStatus $status;

    #[ORM\Column(type: Types::STRING, length: 500, name: 'abnormal_reason', nullable: true, options: ['comment' => '异常原因'])]
    #[Assert\Length(max: 500, maxMessage: '异常原因不能超过500个字符')]
    private ?string $abnormalReason;

    public function __construct()
    {
        $this->status = AttendanceStatus::NORMAL;
        $this->checkInTime = null;
        $this->checkOutTime = null;
        $this->checkInType = null;
        $this->checkInLocation = null;
        $this->checkOutLocation = null;
        $this->abnormalReason = null;
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

    public function getWorkDate(): \DateTimeImmutable
    {
        return $this->workDate;
    }

    public function setWorkDate(\DateTimeImmutable $workDate): void
    {
        $this->workDate = $workDate;
    }

    public function getCheckInTime(): ?\DateTimeImmutable
    {
        return $this->checkInTime;
    }

    public function setCheckInTime(?\DateTimeImmutable $checkInTime): void
    {
        $this->checkInTime = $checkInTime;
    }

    public function checkIn(\DateTimeImmutable $time, CheckInType $type, ?string $location = null): void
    {
        $this->checkInTime = $time;
        $this->checkInType = $type;
        $this->checkInLocation = $location;
    }

    public function getCheckOutTime(): ?\DateTimeImmutable
    {
        return $this->checkOutTime;
    }

    public function setCheckOutTime(?\DateTimeImmutable $checkOutTime): void
    {
        $this->checkOutTime = $checkOutTime;
    }

    public function checkOut(\DateTimeImmutable $time, ?string $location = null): void
    {
        $this->checkOutTime = $time;
        $this->checkOutLocation = $location;
    }

    public function getCheckInType(): ?CheckInType
    {
        return $this->checkInType;
    }

    public function setCheckInType(?CheckInType $checkInType): void
    {
        $this->checkInType = $checkInType;
    }

    public function getCheckInLocation(): ?string
    {
        return $this->checkInLocation;
    }

    public function setCheckInLocation(?string $checkInLocation): void
    {
        $this->checkInLocation = $checkInLocation;
    }

    public function getCheckOutLocation(): ?string
    {
        return $this->checkOutLocation;
    }

    public function setCheckOutLocation(?string $checkOutLocation): void
    {
        $this->checkOutLocation = $checkOutLocation;
    }

    public function getStatus(): AttendanceStatus
    {
        return $this->status;
    }

    public function setStatus(AttendanceStatus $status): void
    {
        $this->status = $status;
    }

    public function getAbnormalReason(): ?string
    {
        return $this->abnormalReason;
    }

    public function setAbnormalReason(?string $abnormalReason): void
    {
        $this->abnormalReason = $abnormalReason;
    }

    public function getWorkDurationMinutes(): ?int
    {
        if (null === $this->checkInTime || null === $this->checkOutTime) {
            return null;
        }

        $diff = $this->checkOutTime->diff($this->checkInTime);

        return $diff->h * 60 + $diff->i;
    }

    public function hasCheckIn(): bool
    {
        return null !== $this->checkInTime;
    }

    public function hasCheckOut(): bool
    {
        return null !== $this->checkOutTime;
    }

    public function isComplete(): bool
    {
        return $this->hasCheckIn() && $this->hasCheckOut();
    }

    public function isNormal(): bool
    {
        return AttendanceStatus::NORMAL === $this->status;
    }

    public function isLate(): bool
    {
        return AttendanceStatus::LATE === $this->status;
    }

    public function isEarlyLeave(): bool
    {
        return AttendanceStatus::EARLY === $this->status;
    }

    public function isAbsent(): bool
    {
        return AttendanceStatus::ABSENT === $this->status;
    }

    public function isOnLeave(): bool
    {
        return AttendanceStatus::LEAVE === $this->status;
    }

    public function isOvertime(): bool
    {
        return AttendanceStatus::OVERTIME === $this->status;
    }

    public function markAsAbnormal(string $reason): void
    {
        $this->abnormalReason = $reason;
    }

    public function clearAbnormal(): void
    {
        $this->abnormalReason = null;
        $this->status = AttendanceStatus::NORMAL;
    }

    public function __toString(): string
    {
        $dateStr = $this->workDate->format('Y-m-d');

        return sprintf('员工%d %s考勤记录 (%s)', $this->employeeId, $dateStr, $this->status->value);
    }
}
