<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\AttendanceManageBundle\Entity\AttendanceRecord;
use Tourze\AttendanceManageBundle\Enum\AttendanceStatus;
use Tourze\AttendanceManageBundle\Enum\CheckInType;

class AttendanceRecordFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $this->createTodayRecords($manager);
        $this->createYesterdayRecords($manager);
        $this->createWeekRecords($manager);
        $this->createAbnormalRecords($manager);

        $manager->flush();
    }

    private function createTodayRecords(ObjectManager $manager): void
    {
        $today = new \DateTimeImmutable('today');

        // 技术部员工正常打卡记录
        $techEmployees = [101, 102, 103, 104, 105];
        foreach ($techEmployees as $employeeId) {
            $record = new AttendanceRecord();
            $record->setEmployeeId($employeeId);
            $record->setWorkDate($today);

            // 上班打卡 (9:00-9:15之间)
            $checkInTime = $today->setTime(9, rand(0, 15), rand(0, 59));
            $record->checkIn($checkInTime, CheckInType::CARD, '39.9042,116.4074');

            // 80%的员工已经下班打卡
            if (rand(1, 100) <= 80) {
                $checkOutTime = $today->setTime(18, rand(0, 30), rand(0, 59));
                $record->checkOut($checkOutTime);
            }

            $manager->persist($record);
        }

        // 销售部员工弹性打卡记录
        $salesEmployees = [201, 202, 203, 204];
        foreach ($salesEmployees as $employeeId) {
            $record = new AttendanceRecord();
            $record->setEmployeeId($employeeId);
            $record->setWorkDate($today);

            // 弹性上班时间 (8:30-9:30之间)
            $checkInTime = $today->setTime(8, rand(30, 59), rand(0, 59))
                ->modify(rand(0, 60) . ' minutes')
            ;
            $record->checkIn($checkInTime, CheckInType::APP, '39.9042,116.4074');

            // 部分员工外出工作，使用APP打卡
            if (rand(1, 100) <= 60) {
                $checkOutTime = $today->setTime(17, rand(30, 59), rand(0, 59))
                    ->modify(rand(0, 90) . ' minutes')
                ;
                $record->checkOut($checkOutTime);
            }

            $manager->persist($record);
        }

        // 人事部标准打卡记录
        $hrEmployees = [301, 302, 303];
        foreach ($hrEmployees as $employeeId) {
            $record = new AttendanceRecord();
            $record->setEmployeeId($employeeId);
            $record->setWorkDate($today);

            // 标准上班时间 8:30
            $checkInTime = $today->setTime(8, 30, rand(0, 59))
                ->modify(rand(-5, 10) . ' minutes') // 可能早到或迟到
            ;
            $record->checkIn($checkInTime, CheckInType::FACE, '39.9042,116.4074');

            // 标准下班时间 17:30
            if (rand(1, 100) <= 90) {
                $checkOutTime = $today->setTime(17, 30, rand(0, 59));
                $record->checkOut($checkOutTime);
            }

            $manager->persist($record);
        }
    }

    private function createYesterdayRecords(ObjectManager $manager): void
    {
        $yesterday = new \DateTimeImmutable('yesterday');

        // 完整的昨天打卡记录，用于统计和分析
        $allEmployees = [
            101, 102, 103, 104, 105, // 技术部
            201, 202, 203, 204,      // 销售部
            301, 302, 303,           // 人事部
            401, 402, 403,           // 客服部
        ];

        foreach ($allEmployees as $employeeId) {
            $record = new AttendanceRecord();
            $record->setEmployeeId($employeeId);
            $record->setWorkDate($yesterday);

            // 根据不同部门设置不同的打卡时间
            $techDept = [101, 102, 103, 104, 105];
            $salesDept = [201, 202, 203, 204];
            $hrDept = [301, 302, 303];

            if (in_array($employeeId, $techDept, true)) {
                // 技术部：9:00标准时间
                $checkInTime = $yesterday->setTime(9, rand(0, 20), rand(0, 59))
                    ->modify(rand(-5, 10) . ' minutes') // 可能早到或迟到
                ;
                $checkOutTime = $yesterday->setTime(18, rand(0, 60), rand(0, 59));
                $checkInType = CheckInType::CARD;
            } elseif (in_array($employeeId, $salesDept, true)) {
                // 销售部：弹性时间
                $checkInTime = $yesterday->setTime(8, rand(45, 59), rand(0, 59))
                    ->modify(rand(0, 45) . ' minutes')
                ;
                $checkOutTime = $yesterday->setTime(18, rand(0, 90), rand(0, 59));
                $checkInType = CheckInType::APP;
            } elseif (in_array($employeeId, $hrDept, true)) {
                // 人事部：8:30标准时间
                $checkInTime = $yesterday->setTime(8, 30, rand(0, 59))
                    ->modify(rand(-10, 15) . ' minutes')
                ;
                $checkOutTime = $yesterday->setTime(17, 30, rand(0, 59));
                $checkInType = CheckInType::FACE;
            } else {
                // 其他部门：10:00弹性时间
                $checkInTime = $yesterday->setTime(10, rand(0, 30), rand(0, 59))
                    ->modify(rand(-30, 30) . ' minutes')
                ;
                $checkOutTime = $yesterday->setTime(19, rand(0, 30), rand(0, 59));
                $checkInType = CheckInType::WIFI;
            }

            $record->checkIn($checkInTime, $checkInType, '39.9042,116.4074');
            $record->checkOut($checkOutTime);

            $manager->persist($record);
        }
    }

    private function createWeekRecords(ObjectManager $manager): void
    {
        // 创建过去一周的打卡记录，用于周报表统计
        $startDate = new \DateTimeImmutable('-7 days');
        $endDate = new \DateTimeImmutable('-2 days');

        $employees = [101, 102, 201, 202, 301]; // 选择几个代表员工

        $current = $startDate;
        while ($current <= $endDate) {
            // 跳过周末
            if ((int) $current->format('N') >= 6) {
                $current = $current->modify('+1 day');
                continue;
            }

            foreach ($employees as $employeeId) {
                // 90%的出勤率
                if (rand(1, 100) <= 90) {
                    $record = new AttendanceRecord();
                    $record->setEmployeeId($employeeId);
                    $record->setWorkDate($current);

                    $checkInTime = $current->setTime(9, rand(0, 30), rand(0, 59))
                        ->modify(rand(-10, 30) . ' minutes')
                    ;
                    $checkOutTime = $current->setTime(18, rand(0, 60), rand(0, 59));

                    $record->checkIn($checkInTime, CheckInType::CARD, '39.9042,116.4074');
                    $record->checkOut($checkOutTime);

                    $manager->persist($record);
                }
            }

            $current = $current->modify('+1 day');
        }
    }

    private function createAbnormalRecords(ObjectManager $manager): void
    {
        $today = new \DateTimeImmutable('today');
        $yesterday = new \DateTimeImmutable('yesterday');

        // 迟到记录
        $lateRecord = new AttendanceRecord();
        $lateRecord->setEmployeeId(106);
        $lateRecord->setWorkDate($today);
        $lateTime = $today->setTime(9, 45, 0); // 迟到45分钟
        $lateRecord->checkIn($lateTime, CheckInType::APP, '39.9042,116.4074');
        $lateRecord->setStatus(AttendanceStatus::LATE);
        $lateRecord->setAbnormalReason('交通堵塞导致迟到');
        $manager->persist($lateRecord);

        // 早退记录
        $earlyRecord = new AttendanceRecord();
        $earlyRecord->setEmployeeId(107);
        $earlyRecord->setWorkDate($today);
        $earlyRecord->checkIn($today->setTime(9, 0, 0), CheckInType::CARD, '39.9042,116.4074');
        $earlyRecord->checkOut($today->setTime(16, 30, 0)); // 早退1.5小时
        $earlyRecord->setStatus(AttendanceStatus::EARLY);
        $earlyRecord->setAbnormalReason('家里有急事需要早退');
        $manager->persist($earlyRecord);

        // 缺勤记录（只有理论记录，没有实际打卡）
        $absentRecord = new AttendanceRecord();
        $absentRecord->setEmployeeId(108);
        $absentRecord->setWorkDate($today);
        $absentRecord->setStatus(AttendanceStatus::ABSENT);
        $absentRecord->setAbnormalReason('病假未及时请假');
        $manager->persist($absentRecord);

        // 加班记录
        $overtimeRecord = new AttendanceRecord();
        $overtimeRecord->setEmployeeId(109);
        $overtimeRecord->setWorkDate($yesterday);
        $overtimeRecord->checkIn($yesterday->setTime(9, 0, 0), CheckInType::CARD, '39.9042,116.4074');
        $overtimeRecord->checkOut($yesterday->setTime(21, 30, 0)); // 加班3.5小时
        $overtimeRecord->setStatus(AttendanceStatus::OVERTIME);
        $manager->persist($overtimeRecord);

        // 异常打卡位置记录
        $locationRecord = new AttendanceRecord();
        $locationRecord->setEmployeeId(110);
        $locationRecord->setWorkDate($today);
        $locationRecord->checkIn($today->setTime(9, 15, 0), CheckInType::APP, '31.2304,121.4737'); // 上海位置
        $locationRecord->setAbnormalReason('出差期间异地打卡');
        $manager->persist($locationRecord);

        // 设备异常记录
        $deviceRecord = new AttendanceRecord();
        $deviceRecord->setEmployeeId(111);
        $deviceRecord->setWorkDate($today);
        $deviceRecord->checkIn($today->setTime(9, 5, 0), CheckInType::APP, '39.9042,116.4074');
        $deviceRecord->setAbnormalReason('考勤机故障，使用手机补打卡');
        $manager->persist($deviceRecord);

        // 跨天班次记录（夜班）
        $nightShiftRecord = new AttendanceRecord(); // 生产部员工
        $nightShiftRecord->setEmployeeId(505);
        $nightShiftRecord->setWorkDate($yesterday);
        $nightShiftStart = $yesterday->setTime(22, 0, 0);
        $nightShiftEnd = $today->setTime(6, 0, 0);

        $nightShiftRecord->checkIn($nightShiftStart, CheckInType::CARD, '39.9042,116.4074');
        $nightShiftRecord->checkOut($nightShiftEnd);
        $manager->persist($nightShiftRecord);
    }

    /**
     * @return array<class-string<FixtureInterface>>
     */
    public function getDependencies(): array
    {
        return [
            AttendanceGroupFixtures::class,
        ];
    }
}
