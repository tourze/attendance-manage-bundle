<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\AttendanceManageBundle\Repository\WorkShiftRepository;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: WorkShiftRepository::class)]
#[ORM\Table(name: 'work_shifts', options: ['comment' => '工作班次表'])]
class WorkShift implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private int $id;

    #[IndexColumn]
    #[ORM\Column(type: Types::INTEGER, name: 'group_id', options: ['comment' => '考勤组ID'])]
    #[Assert\NotNull(message: '考勤组ID不能为空')]
    #[Assert\Positive(message: '考勤组ID必须为正数')]
    private int $groupId;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '班次名称'])]
    #[Assert\NotBlank(message: '班次名称不能为空')]
    #[Assert\Length(max: 100, maxMessage: '班次名称不能超过100个字符')]
    private string $name;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, name: 'start_time', options: ['comment' => '开始时间'])]
    #[Assert\NotNull(message: '开始时间不能为空')]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, name: 'end_time', options: ['comment' => '结束时间'])]
    #[Assert\NotNull(message: '结束时间不能为空')]
    private \DateTimeImmutable $endTime;

    #[ORM\Column(type: Types::INTEGER, name: 'flexible_minutes', nullable: true, options: ['comment' => '弹性时间(分钟)'])]
    #[Assert\Range(min: 0, max: 120, notInRangeMessage: '弹性时间必须在0-120分钟之间')]
    private ?int $flexibleMinutes;

    /**
     * @var array<array{start: string, end: string}>
     */
    #[ORM\Column(type: Types::JSON, name: 'break_times', options: ['comment' => '休息时间配置'])]
    #[Assert\NotNull(message: '休息时间不能为空')]
    #[Assert\Type(type: 'array', message: '休息时间必须为数组')]
    private array $breakTimes;

    #[ORM\Column(type: Types::BOOLEAN, name: 'cross_day', options: ['comment' => '是否跨天班次'])]
    #[Assert\NotNull(message: '跨天标识不能为空')]
    private bool $crossDay;

    #[ORM\Column(type: Types::BOOLEAN, name: 'is_active', options: ['comment' => '是否启用'])]
    #[Assert\NotNull(message: '启用状态不能为空')]
    private bool $isActive;

    public function __construct()
    {
        $this->groupId = 0;
        $this->name = '';
        $this->startTime = new \DateTimeImmutable();
        $this->endTime = new \DateTimeImmutable();
        $this->flexibleMinutes = null;
        $this->breakTimes = [];
        $this->crossDay = false;
        $this->isActive = true;
        $this->createTime = new \DateTimeImmutable();
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function setGroupId(int $groupId): void
    {
        $this->groupId = $groupId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getStartTime(): \DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function getEndTime(): \DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeImmutable $endTime): void
    {
        $this->endTime = $endTime;
    }

    public function getFlexibleMinutes(): ?int
    {
        return $this->flexibleMinutes;
    }

    public function setFlexibleMinutes(?int $flexibleMinutes): void
    {
        $this->flexibleMinutes = $flexibleMinutes;
    }

    /**
     * @return array<array{start: string, end: string}>
     */
    public function getBreakTimes(): array
    {
        return $this->breakTimes;
    }

    /**
     * @param array<array{start: string, end: string}> $breakTimes
     */
    public function setBreakTimes(array $breakTimes): void
    {
        $this->breakTimes = $breakTimes;
    }

    public function addBreakTime(string $startTime, string $endTime): void
    {
        $this->breakTimes[] = ['start' => $startTime, 'end' => $endTime];
    }

    public function isCrossDay(): bool
    {
        return $this->crossDay;
    }

    public function setCrossDay(bool $crossDay): void
    {
        $this->crossDay = $crossDay;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getTotalBreakMinutes(): int
    {
        $totalMinutes = 0;
        foreach ($this->breakTimes as $breakTime) {
            $start = \DateTimeImmutable::createFromFormat('H:i', $breakTime['start']);
            $end = \DateTimeImmutable::createFromFormat('H:i', $breakTime['end']);
            if (false !== $start && false !== $end) {
                $diff = $end->diff($start);
                $totalMinutes += $diff->h * 60 + $diff->i;
            }
        }

        return $totalMinutes;
    }

    public function getWorkDurationMinutes(): int
    {
        if ($this->crossDay) {
            // 跨天班次：22:00到次日06:00
            $startMinutes = $this->startTime->format('H') * 60 + $this->startTime->format('i');
            $endMinutes = $this->endTime->format('H') * 60 + $this->endTime->format('i');

            // 从开始时间到午夜，再从午夜到结束时间
            $workMinutes = (24 * 60 - $startMinutes) + $endMinutes;
        } else {
            $diff = $this->startTime->diff($this->endTime);
            $workMinutes = $diff->h * 60 + $diff->i;

            if ($workMinutes < 0) {
                $workMinutes += 24 * 60; // 处理跨天情况
            }
        }

        return (int) ($workMinutes - $this->getTotalBreakMinutes());
    }

    public function isWithinFlexibleRange(\DateTimeInterface $checkTime): bool
    {
        if (null === $this->flexibleMinutes) {
            return false;
        }

        $startWithFlex = $this->startTime->modify("-{$this->flexibleMinutes} minutes");
        $endWithFlex = $this->startTime->modify("+{$this->flexibleMinutes} minutes");

        return $checkTime >= $startWithFlex && $checkTime <= $endWithFlex;
    }

    public function __toString(): string
    {
        return sprintf('%s (%s-%s)', $this->name, $this->startTime->format('H:i'), $this->endTime->format('H:i'));
    }
}
