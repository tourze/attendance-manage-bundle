<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Service;

use Tourze\AttendanceManageBundle\Entity\HolidayConfig;
use Tourze\AttendanceManageBundle\Exception\AttendanceException;
use Tourze\AttendanceManageBundle\Repository\HolidayConfigRepository;

readonly class HolidayService
{
    public function __construct(
        private HolidayConfigRepository $repository,
    ) {
    }

    /**
     * @param array<int, string>|null $applicableDepartments
     */
    public function createHoliday(
        string $name,
        \DateTimeImmutable $date,
        string $type,
        ?string $description = null,
        bool $isPaid = true,
        bool $isMandatory = true,
        ?array $applicableDepartments = null,
    ): HolidayConfig {
        $holiday = new HolidayConfig();
        $holiday->setName($name);
        $holiday->setHolidayDate($date);
        $holiday->setType($type);
        $holiday->setDescription($description);
        $holiday->setPaid($isPaid);
        $holiday->setMandatory($isMandatory);
        $holiday->setApplicableDepartments($applicableDepartments);
        $this->repository->save($holiday, true);

        return $holiday;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateHoliday(int $holidayId, array $data): HolidayConfig
    {
        $holiday = $this->repository->find($holidayId);
        if (!$holiday instanceof HolidayConfig) {
            throw AttendanceException::recordNotFound($holidayId);
        }

        if (isset($data['name'])) {
            if (!is_string($data['name'])) {
                throw AttendanceException::invalidCheckIn('假期名称必须是字符串');
            }
            $holiday->setName($data['name']);
        }

        if (isset($data['description'])) {
            $description = $data['description'];
            if (is_string($description)) {
                $holiday->setDescription($description);
            } else {
                throw AttendanceException::invalidCheckIn('假期描述必须是字符串');
            }
        }

        if (isset($data['isPaid'])) {
            $holiday->setPaid((bool) $data['isPaid']);
        }

        $this->repository->save($holiday, true);

        return $holiday;
    }

    public function deleteHoliday(int $holidayId): void
    {
        $holiday = $this->repository->find($holidayId);
        if (!$holiday instanceof HolidayConfig) {
            throw AttendanceException::recordNotFound($holidayId);
        }

        $holiday->setActive(false);
        $this->repository->save($holiday, true);
    }

    public function getHolidayById(int $holidayId): ?HolidayConfig
    {
        return $this->repository->find($holidayId);
    }

    /**
     * @return array<HolidayConfig>
     */
    public function getHolidaysByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        return $this->repository->findByDateRange($startDate, $endDate);
    }

    /**
     * @return array<HolidayConfig>
     */
    public function getActiveHolidays(): array
    {
        return $this->repository->findBy(['isActive' => true], ['holidayDate' => 'ASC']);
    }

    public function isHoliday(\DateTimeImmutable $date): bool
    {
        $holiday = $this->repository->findOneBy(['holidayDate' => $date, 'isActive' => true]);

        return null !== $holiday;
    }

    public function isWorkingDay(\DateTimeImmutable $date): bool
    {
        // 检查是否是周末
        $dayOfWeek = (int) $date->format('N');
        if ($dayOfWeek >= 6) { // 6=Saturday, 7=Sunday
            return false;
        }

        // 检查是否是假期
        return !$this->isHoliday($date);
    }

    /**
     * @return array<HolidayConfig>
     */
    public function getHolidaysByType(string $type): array
    {
        return $this->repository->findBy(['type' => $type, 'isActive' => true], ['holidayDate' => 'ASC']);
    }

    public function isApplicableToEmployee(int $holidayId, string $departmentId): bool
    {
        $holiday = $this->repository->find($holidayId);
        if (!$holiday instanceof HolidayConfig) {
            return false;
        }

        return $holiday->isApplicableToDepartment($departmentId);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function validateHolidayData(array $data): bool
    {
        if (!isset($data['name']) || '' === $data['name'] || !is_string($data['name'])) {
            return false;
        }

        if (!isset($data['type']) || '' === $data['type'] || !is_string($data['type'])) {
            return false;
        }

        $validTypes = [HolidayConfig::TYPE_NATIONAL, HolidayConfig::TYPE_COMPANY, HolidayConfig::TYPE_SPECIAL];
        if (!in_array($data['type'], $validTypes, true)) {
            return false;
        }

        if (!isset($data['date']) || !($data['date'] instanceof \DateTimeInterface)) {
            return false;
        }

        if (isset($data['isPaid']) && !is_bool($data['isPaid'])) {
            return false;
        }

        if (isset($data['isMandatory']) && !is_bool($data['isMandatory'])) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getHolidayConfig(int $holidayId): ?array
    {
        $holiday = $this->repository->find($holidayId);
        if (!$holiday instanceof HolidayConfig) {
            return null;
        }

        return [
            'id' => $holiday->getId(),
            'name' => $holiday->getName(),
            'date' => $holiday->getHolidayDate(),
            'type' => $holiday->getType(),
            'description' => $holiday->getDescription(),
            'isPaid' => $holiday->isPaid(),
            'isMandatory' => $holiday->isMandatory(),
            'applicableDepartments' => $holiday->getApplicableDepartments(),
            'isActive' => $holiday->isActive(),
        ];
    }
}
