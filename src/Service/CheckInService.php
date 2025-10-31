<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\Service;

use Tourze\AttendanceManageBundle\Entity\AttendanceRecord;
use Tourze\AttendanceManageBundle\Enum\CheckInType;
use Tourze\AttendanceManageBundle\Exception\CheckInException;
use Tourze\AttendanceManageBundle\Repository\AttendanceRecordRepository;

readonly class CheckInService
{
    public function __construct(
        private AttendanceRecordRepository $recordRepository,
        private RuleService $ruleService,
        private AttendanceStatusCalculator $statusCalculator,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function checkIn(int $employeeId, CheckInType $type, array $data = []): AttendanceRecord
    {
        $now = new \DateTimeImmutable();
        $today = $now->setTime(0, 0, 0);

        // 查询现有记录一次，避免重复调用
        $existingRecord = $this->recordRepository->findByEmployeeAndDate($employeeId, $today);

        // 检查是否可以签到
        if (null !== $existingRecord && $existingRecord->hasCheckIn()) {
            throw CheckInException::alreadyCheckedIn();
        }

        $this->validateCheckInData($employeeId, $type, $data, $now, $existingRecord);

        $record = $existingRecord ?? new AttendanceRecord();
        if (null === $existingRecord) {
            $record->setEmployeeId($employeeId);
            $record->setWorkDate($today);
            $this->recordRepository->save($record);
        }

        $location = $this->formatLocation($data);
        $record->checkIn($now, $type, $location);

        $status = $this->statusCalculator->calculateCheckInStatus($employeeId, $now);
        $record->setStatus($status);

        $this->recordRepository->save($record, true);

        return $record;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function checkOut(int $employeeId, CheckInType $type, array $data = []): AttendanceRecord
    {
        $now = new \DateTimeImmutable();
        $today = $now->setTime(0, 0, 0);

        $record = $this->recordRepository->findByEmployeeAndDate($employeeId, $today);
        if (null === $record) {
            throw CheckInException::mustCheckInFirst();
        }

        if (!$record->hasCheckIn() || $record->hasCheckOut()) {
            throw CheckInException::alreadyCheckedOut();
        }

        $this->validateCheckOutData($employeeId, $type, $data, $now);

        $location = $this->formatLocation($data);
        $record->checkOut($now, $location);

        $status = $this->statusCalculator->calculateCheckOutStatus($record, $now);
        $record->setStatus($status);

        $this->recordRepository->save($record, true);

        return $record;
    }

    public function getTodayRecord(int $employeeId): ?AttendanceRecord
    {
        $today = new \DateTimeImmutable();
        $today = $today->setTime(0, 0, 0);

        return $this->recordRepository->findByEmployeeAndDate($employeeId, $today);
    }

    public function canCheckIn(int $employeeId): bool
    {
        $record = $this->getTodayRecord($employeeId);

        if (null === $record) {
            return true;
        }

        return !$record->hasCheckIn();
    }

    public function canCheckOut(int $employeeId): bool
    {
        $record = $this->getTodayRecord($employeeId);

        if (null === $record || !$record->hasCheckIn()) {
            return false;
        }

        return !$record->hasCheckOut();
    }

    /**
     * @param array<string, mixed> $location
     */
    public function validateLocation(array $location): bool
    {
        if (!isset($location['lat']) || !isset($location['lng'])) {
            return false;
        }

        // 检查是否为有效的数值
        if (!is_numeric($location['lat']) || !is_numeric($location['lng'])) {
            return false;
        }

        $lat = (float) $location['lat'];
        $lng = (float) $location['lng'];

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return false;
        }

        return true;
    }

    public function validateDevice(string $deviceId): bool
    {
        if ('' === $deviceId || strlen($deviceId) < 3) {
            return false;
        }

        return true;
    }

    public function preventDuplicateCheckIn(int $employeeId, \DateTimeInterface $time): bool
    {
        $since = new \DateTime($time->format('Y-m-d H:i:s'));
        $since->modify('-1 hour'); // 查找1小时内的记录
        $record = $this->recordRepository->findRecentRecord($employeeId, $since);

        if (null === $record || null === $record->getCheckInTime()) {
            return true;
        }

        $lastCheckIn = $record->getCheckInTime();
        $timeDiff = $time->getTimestamp() - $lastCheckIn->getTimestamp();

        return $timeDiff >= 60;
    }

    private function preventDuplicateCheckInWithRecord(int $employeeId, \DateTimeInterface $time, ?AttendanceRecord $record): bool
    {
        if (null === $record || null === $record->getCheckInTime()) {
            return true;
        }

        $lastCheckIn = $record->getCheckInTime();
        $timeDiff = $time->getTimestamp() - $lastCheckIn->getTimestamp();

        return $timeDiff >= 60;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateCheckInData(int $employeeId, CheckInType $type, array $data, \DateTimeInterface $checkTime, ?AttendanceRecord $existingRecord = null): void
    {
        $this->validateLocationData($data);
        $this->validateDeviceData($data);
        $this->validateDuplicateCheckIn($employeeId, $checkTime, $existingRecord);
        $this->validateAttendanceRules($employeeId, $checkTime, 'check_in');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateLocationData(array $data): void
    {
        if (!isset($data['location'])) {
            return;
        }

        if (!is_array($data['location'])) {
            throw CheckInException::invalidLocation();
        }

        /** @var array<string, mixed> $location */
        $location = $data['location'];
        if (!$this->validateLocation($location)) {
            throw CheckInException::invalidLocation();
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateDeviceData(array $data): void
    {
        if (!isset($data['deviceId'])) {
            return;
        }

        if (!is_string($data['deviceId']) || !$this->validateDevice($data['deviceId'])) {
            $deviceId = is_string($data['deviceId']) ? $data['deviceId'] : 'unknown';
            throw CheckInException::deviceNotAllowed($deviceId);
        }
    }

    private function validateDuplicateCheckIn(int $employeeId, \DateTimeInterface $checkTime, ?AttendanceRecord $existingRecord = null): void
    {
        if (!$this->preventDuplicateCheckInWithRecord($employeeId, $checkTime, $existingRecord)) {
            throw CheckInException::alreadyCheckedIn();
        }
    }

    private function validateAttendanceRules(int $employeeId, \DateTimeInterface $checkTime, string $checkType): void
    {
        $validation = $this->ruleService->validateAttendance($employeeId, $checkTime, $checkType);
        if (isset($validation['violations']) && count($validation['violations']) > 0) {
            throw CheckInException::invalidCheckIn(implode('; ', $validation['violations']));
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateCheckOutData(int $employeeId, CheckInType $type, array $data, \DateTimeInterface $checkTime): void
    {
        $validation = $this->ruleService->validateAttendance($employeeId, $checkTime, 'check_out');
        if (isset($validation['violations']) && count($validation['violations']) > 0) {
            throw CheckInException::invalidCheckIn(implode('; ', $validation['violations']));
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function formatLocation(array $data): ?string
    {
        if (!isset($data['location'])) {
            return null;
        }

        $location = $data['location'];
        if (!is_array($location) || !isset($location['lat']) || !isset($location['lng'])) {
            return null;
        }

        if (!is_numeric($location['lat']) || !is_numeric($location['lng'])) {
            return null;
        }

        $address = '';
        if (isset($location['address']) && is_string($location['address'])) {
            $address = $location['address'];
        }

        return sprintf(
            '%.6f,%.6f%s',
            (float) $location['lat'],
            (float) $location['lng'],
            ('' !== $address) ? " ({$address})" : ''
        );
    }
}
