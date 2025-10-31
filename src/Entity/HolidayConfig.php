<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\AttendanceManageBundle\Exception\AttendanceException;
use Tourze\AttendanceManageBundle\Repository\HolidayConfigRepository;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: HolidayConfigRepository::class)]
#[ORM\Table(name: 'holiday_configs', options: ['comment' => '节假日配置表'])]
class HolidayConfig implements \Stringable
{
    use TimestampableAware;
    public const TYPE_NATIONAL = 'national';
    public const TYPE_COMPANY = 'company';
    public const TYPE_SPECIAL = 'special';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private int $id; // @phpstan-ignore-line

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '节假日名称'])]
    #[Assert\NotBlank(message: '节假日名称不能为空')]
    #[Assert\Length(max: 100, maxMessage: '节假日名称不能超过100个字符')]
    private string $name;

    #[IndexColumn]
    #[ORM\Column(type: Types::DATE_IMMUTABLE, name: 'holiday_date', options: ['comment' => '节假日日期'])]
    #[Assert\NotNull(message: '节假日日期不能为空')]
    private \DateTimeImmutable $holidayDate;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '节假日类型'])]
    #[Assert\NotBlank(message: '节假日类型不能为空')]
    #[Assert\Length(max: 20, maxMessage: '节假日类型不能超过20个字符')]
    #[Assert\Choice(choices: [self::TYPE_NATIONAL, self::TYPE_COMPANY, self::TYPE_SPECIAL], message: '无效的节假日类型')]
    private string $type;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '节假日描述'])]
    #[Assert\Length(max: 1000, maxMessage: '描述不能超过1000个字符')]
    private ?string $description;

    #[ORM\Column(type: Types::BOOLEAN, name: 'is_paid', options: ['comment' => '是否带薪'])]
    #[Assert\NotNull(message: '是否带薪不能为空')]
    private bool $isPaid;

    #[ORM\Column(type: Types::BOOLEAN, name: 'is_mandatory', options: ['comment' => '是否强制休假'])]
    #[Assert\NotNull(message: '是否强制休假不能为空')]
    private bool $isMandatory;

    /**
     * @var array<int, string>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '适用部门'])]
    #[Assert\All(constraints: [
        new Assert\Type(type: 'string'),
        new Assert\NotBlank(message: '部门ID不能为空'),
    ])]
    private ?array $applicableDepartments;

    #[IndexColumn]
    #[ORM\Column(type: Types::BOOLEAN, name: 'is_active', options: ['comment' => '是否启用'])]
    #[Assert\NotNull(message: '启用状态不能为空')]
    private bool $isActive;

    public function __construct()
    {
        $this->isActive = true;
        $this->createTime = new \DateTimeImmutable();
        $this->updateTime = new \DateTimeImmutable();
        $this->isPaid = true;
        $this->isMandatory = true;
        $this->description = null;
        $this->applicableDepartments = null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        if ('' === $name) {
            throw AttendanceException::invalidCheckIn('节假日名称不能为空');
        }
        $this->name = $name;
    }

    public function getHolidayDate(): \DateTimeImmutable
    {
        return $this->holidayDate;
    }

    public function setHolidayDate(\DateTimeImmutable $holidayDate): void
    {
        $this->holidayDate = $holidayDate;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        if (!in_array($type, [self::TYPE_NATIONAL, self::TYPE_COMPANY, self::TYPE_SPECIAL], true)) {
            throw AttendanceException::invalidCheckIn('无效的节假日类型');
        }
        $this->type = $type;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function isPaid(): bool
    {
        return $this->isPaid;
    }

    public function setPaid(bool $isPaid): void
    {
        $this->isPaid = $isPaid;
    }

    public function isMandatory(): bool
    {
        return $this->isMandatory;
    }

    public function setMandatory(bool $isMandatory): void
    {
        $this->isMandatory = $isMandatory;
    }

    /**
     * @return array<int, string>|null
     */
    public function getApplicableDepartments(): ?array
    {
        return $this->applicableDepartments;
    }

    /**
     * @param array<int, string>|null $applicableDepartments
     */
    public function setApplicableDepartments(?array $applicableDepartments): void
    {
        $this->applicableDepartments = $applicableDepartments;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function isNationalHoliday(): bool
    {
        return self::TYPE_NATIONAL === $this->type;
    }

    public function isCompanyHoliday(): bool
    {
        return self::TYPE_COMPANY === $this->type;
    }

    public function isSpecialHoliday(): bool
    {
        return self::TYPE_SPECIAL === $this->type;
    }

    public function isApplicableToAllDepartments(): bool
    {
        return null === $this->applicableDepartments || [] === $this->applicableDepartments;
    }

    public function isApplicableToDepartment(string $departmentId): bool
    {
        if ($this->isApplicableToAllDepartments()) {
            return true;
        }

        return null !== $this->applicableDepartments && in_array($departmentId, $this->applicableDepartments, true);
    }

    public function addApplicableDepartment(string $departmentId): void
    {
        if (null === $this->applicableDepartments) {
            $this->applicableDepartments = [];
        }

        if (!in_array($departmentId, $this->applicableDepartments, true)) {
            $this->applicableDepartments[] = $departmentId;
        }
    }

    public function removeApplicableDepartment(string $departmentId): void
    {
        if (null !== $this->applicableDepartments) {
            $key = array_search($departmentId, $this->applicableDepartments, true);
            if (false !== $key) {
                unset($this->applicableDepartments[$key]);
                $this->applicableDepartments = array_values($this->applicableDepartments);
            }
        }
    }

    public function isToday(): bool
    {
        $today = new \DateTimeImmutable();

        return $this->holidayDate->format('Y-m-d') === $today->format('Y-m-d');
    }

    public function isInPast(): bool
    {
        $today = new \DateTimeImmutable();

        return $this->holidayDate < $today;
    }

    public function isInFuture(): bool
    {
        $today = new \DateTimeImmutable();

        return $this->holidayDate > $today;
    }

    public function __toString(): string
    {
        $dateStr = $this->holidayDate->format('Y-m-d');

        return sprintf('%s (%s, %s)', $this->name, $dateStr, $this->type);
    }
}
