<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\AttendanceManageBundle\Enum\AttendanceGroupType;
use Tourze\AttendanceManageBundle\Repository\AttendanceGroupRepository;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: AttendanceGroupRepository::class)]
#[ORM\Table(name: 'attendance_groups', options: ['comment' => '考勤组表'])]
class AttendanceGroup implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private int $id; // @phpstan-ignore-line

    #[ORM\Version]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '版本号，用于乐观锁'])]
    private int $version;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '考勤组名称'])]
    #[Assert\NotBlank(message: '考勤组名称不能为空')]
    #[Assert\Length(max: 100, maxMessage: '考勤组名称不能超过100个字符')]
    private string $name;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 20, enumType: AttendanceGroupType::class, options: ['comment' => '考勤组类型'])]
    #[Assert\NotNull(message: '考勤组类型不能为空')]
    #[Assert\Choice(callback: [AttendanceGroupType::class, 'cases'], message: '无效的考勤组类型')]
    private AttendanceGroupType $type;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '考勤规则配置'])]
    #[Assert\NotNull(message: '考勤规则不能为空')]
    private array $rules;

    /**
     * @var array<int>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '成员用户ID列表'])]
    #[Assert\NotNull(message: '成员列表不能为空')]
    private array $memberIds;

    #[IndexColumn]
    #[ORM\Column(type: Types::BOOLEAN, name: 'is_active', options: ['comment' => '是否启用'])]
    #[Assert\NotNull(message: '启用状态不能为空')]
    private bool $isActive;

    public function __construct()
    {
        $this->rules = [];
        $this->memberIds = [];
        $this->isActive = true;
        $this->version = 1;
        $this->createTime = new \DateTimeImmutable();
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getType(): AttendanceGroupType
    {
        return $this->type;
    }

    public function setType(AttendanceGroupType $type): void
    {
        $this->type = $type;
        $this->updateTime = new \DateTimeImmutable();
    }

    /**
     * @return array<string, mixed>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @param array<string, mixed> $rules
     */
    public function setRules(array $rules): void
    {
        $this->rules = $rules;
        $this->updateTime = new \DateTimeImmutable();
    }

    /**
     * @return array<int>
     */
    public function getMemberIds(): array
    {
        return $this->memberIds;
    }

    /**
     * @param array<int> $memberIds
     */
    public function setMemberIds(array $memberIds): void
    {
        $this->memberIds = $memberIds;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function addMember(int $employeeId): self
    {
        if (!in_array($employeeId, $this->memberIds, true)) {
            $this->memberIds[] = $employeeId;
            $this->updateTime = new \DateTimeImmutable();
        }

        return $this;
    }

    public function removeMember(int $employeeId): self
    {
        $key = array_search($employeeId, $this->memberIds, true);
        if (false !== $key) {
            unset($this->memberIds[$key]);
            $this->memberIds = array_values($this->memberIds);
            $this->updateTime = new \DateTimeImmutable();
        }

        return $this;
    }

    public function hasMember(int $employeeId): bool
    {
        return in_array($employeeId, $this->memberIds, true);
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): void
    {
        $this->isActive = $isActive;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function isFixedTime(): bool
    {
        return $this->type->isFixedTime();
    }

    public function isFlexibleTime(): bool
    {
        return $this->type->isFlexibleTime();
    }

    public function isShiftWork(): bool
    {
        return $this->type->isShiftWork();
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->name, $this->type->value);
    }
}
