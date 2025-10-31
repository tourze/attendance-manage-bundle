<?php

declare(strict_types=1);

namespace Tourze\AttendanceManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\AttendanceManageBundle\Entity\HolidayConfig;

class HolidayConfigFixtures extends Fixture
{
    public const SPRING_FESTIVAL_REFERENCE = 'spring-festival';
    public const NATIONAL_DAY_REFERENCE = 'national-day';
    public const LABOR_DAY_REFERENCE = 'labor-day';

    public function load(ObjectManager $manager): void
    {
        $this->createNationalHolidays2025($manager);
        $this->createCompanyHolidays($manager);
        $this->createSpecialWorkingDays($manager);
        $this->createRegionalHolidays($manager);

        $manager->flush();
    }

    private function createNationalHolidays2025(ObjectManager $manager): void
    {
        // 2025年国家法定节假日

        // 春节假期 (1月28日-2月3日，共7天)
        $springFestival = new HolidayConfig();
        $springFestival->setName('春节假期');
        $springFestival->setHolidayDate(new \DateTimeImmutable('2025-01-28'));
        $springFestival->setType('national');
        $springFestival->setDescription('2025年春节假期，农历新年');
        $manager->persist($springFestival);
        $this->addReference(self::SPRING_FESTIVAL_REFERENCE, $springFestival);

        // 清明节 (4月5日-4月7日，共3天)
        $qingMing = new HolidayConfig();
        $qingMing->setName('清明节');
        $qingMing->setHolidayDate(new \DateTimeImmutable('2025-04-05'));
        $qingMing->setType('national');
        $qingMing->setDescription('清明节假期，祭祀祖先传统节日');
        $manager->persist($qingMing);

        // 劳动节 (5月1日-5月5日，共5天)
        $laborDay = new HolidayConfig();
        $laborDay->setName('劳动节');
        $laborDay->setHolidayDate(new \DateTimeImmutable('2025-05-01'));
        $laborDay->setType('national');
        $laborDay->setDescription('国际劳动节假期');
        $manager->persist($laborDay);
        $this->addReference(self::LABOR_DAY_REFERENCE, $laborDay);

        // 端午节 (5月31日-6月2日，共3天)
        $dragonBoat = new HolidayConfig();
        $dragonBoat->setName('端午节');
        $dragonBoat->setHolidayDate(new \DateTimeImmutable('2025-05-31'));
        $dragonBoat->setType('national');
        $dragonBoat->setDescription('端午节假期，纪念屈原');
        $manager->persist($dragonBoat);

        // 中秋节 (10月6日，共1天)
        $midAutumn = new HolidayConfig();
        $midAutumn->setName('中秋节');
        $midAutumn->setHolidayDate(new \DateTimeImmutable('2025-10-06'));
        $midAutumn->setType('national');
        $midAutumn->setDescription('中秋节假期，家庭团圆节日');
        $manager->persist($midAutumn);

        // 国庆节 (10月1日-10月7日，共7天)
        $nationalDay = new HolidayConfig();
        $nationalDay->setName('国庆节');
        $nationalDay->setHolidayDate(new \DateTimeImmutable('2025-10-01'));
        $nationalDay->setType('national');
        $nationalDay->setDescription('国庆节假期，庆祝中华人民共和国成立');
        $manager->persist($nationalDay);
        $this->addReference(self::NATIONAL_DAY_REFERENCE, $nationalDay);

        // 元旦 (1月1日，共1天)
        $newYear = new HolidayConfig();
        $newYear->setName('元旦');
        $newYear->setHolidayDate(new \DateTimeImmutable('2025-01-01'));
        $newYear->setType('national');
        $newYear->setDescription('元旦假期，新年第一天');
        $manager->persist($newYear);
    }

    private function createCompanyHolidays(ObjectManager $manager): void
    {
        // 公司特有假期和福利假期

        // 公司成立纪念日
        $companyAnniversary = new HolidayConfig();
        $companyAnniversary->setName('公司成立纪念日');
        $companyAnniversary->setHolidayDate(new \DateTimeImmutable('2025-03-15'));
        $companyAnniversary->setType('company');
        $companyAnniversary->setDescription('公司成立周年纪念，全员放假');
        $manager->persist($companyAnniversary);

        // 年度团建日
        $teamBuildingDay = new HolidayConfig();
        $teamBuildingDay->setName('年度团建日');
        $teamBuildingDay->setHolidayDate(new \DateTimeImmutable('2025-07-20'));
        $teamBuildingDay->setType('company');
        $teamBuildingDay->setDescription('公司年度团队建设活动');
        $manager->persist($teamBuildingDay);

        // 冬季调休日
        $winterBreak = new HolidayConfig();
        $winterBreak->setName('冬季调休日');
        $winterBreak->setHolidayDate(new \DateTimeImmutable('2025-12-30'));
        $winterBreak->setType('company');
        $winterBreak->setDescription('年底调休，准备春节假期');
        $manager->persist($winterBreak);

        // 夏季高温假期
        $summerBreak = new HolidayConfig();
        $summerBreak->setName('夏季高温假期');
        $summerBreak->setHolidayDate(new \DateTimeImmutable('2025-08-15'));
        $summerBreak->setType('company');
        $summerBreak->setDescription('夏季高温天气特别假期');
        $manager->persist($summerBreak);

        // 员工福利日
        $employeeBenefitDay = new HolidayConfig();
        $employeeBenefitDay->setName('员工福利日');
        $employeeBenefitDay->setHolidayDate(new \DateTimeImmutable('2025-09-10'));
        $employeeBenefitDay->setType('company');
        $employeeBenefitDay->setDescription('员工福利活动日，下午放假');
        $manager->persist($employeeBenefitDay);
    }

    private function createSpecialWorkingDays(ObjectManager $manager): void
    {
        // 调班工作日（节假日调休的工作日）

        // 春节调班日
        $springFestivalMakeup1 = new HolidayConfig();
        $springFestivalMakeup1->setName('春节调班日1');
        $springFestivalMakeup1->setHolidayDate(new \DateTimeImmutable('2025-01-26'));
        $springFestivalMakeup1->setType('special');
        $springFestivalMakeup1->setDescription('春节假期调班，周日上班');
        $manager->persist($springFestivalMakeup1);

        $springFestivalMakeup2 = new HolidayConfig();
        $springFestivalMakeup2->setName('春节调班日2');
        $springFestivalMakeup2->setHolidayDate(new \DateTimeImmutable('2025-02-08'));
        $springFestivalMakeup2->setType('special');
        $springFestivalMakeup2->setDescription('春节假期调班，周六上班');
        $manager->persist($springFestivalMakeup2);

        // 劳动节调班日
        $laborDayMakeup = new HolidayConfig();
        $laborDayMakeup->setName('劳动节调班日');
        $laborDayMakeup->setHolidayDate(new \DateTimeImmutable('2025-04-27'));
        $laborDayMakeup->setType('special');
        $laborDayMakeup->setDescription('劳动节假期调班，周日上班');
        $manager->persist($laborDayMakeup);

        // 国庆节调班日
        $nationalDayMakeup1 = new HolidayConfig();
        $nationalDayMakeup1->setName('国庆节调班日1');
        $nationalDayMakeup1->setHolidayDate(new \DateTimeImmutable('2025-09-28'));
        $nationalDayMakeup1->setType('special');
        $nationalDayMakeup1->setDescription('国庆节假期调班，周日上班');
        $manager->persist($nationalDayMakeup1);

        $nationalDayMakeup2 = new HolidayConfig();
        $nationalDayMakeup2->setName('国庆节调班日2');
        $nationalDayMakeup2->setHolidayDate(new \DateTimeImmutable('2025-10-11'));
        $nationalDayMakeup2->setType('special');
        $nationalDayMakeup2->setDescription('国庆节假期调班，周六上班');
        $manager->persist($nationalDayMakeup2);
    }

    private function createRegionalHolidays(ObjectManager $manager): void
    {
        // 地区性特殊假期（根据公司所在地）

        // 北京地区特殊假期
        $beijingSpecial = new HolidayConfig();
        $beijingSpecial->setName('北京地区特殊假期');
        $beijingSpecial->setHolidayDate(new \DateTimeImmutable('2025-11-15'));
        $beijingSpecial->setType('special');
        $beijingSpecial->setDescription('北京地区特殊活动假期');
        $manager->persist($beijingSpecial);

        // 传统文化节日（非法定）
        $traditionalCulture = new HolidayConfig();
        $traditionalCulture->setName('传统文化节');
        $traditionalCulture->setHolidayDate(new \DateTimeImmutable('2025-06-15'));
        $traditionalCulture->setType('company');
        $traditionalCulture->setDescription('公司推广传统文化活动日');
        $manager->persist($traditionalCulture);

        // 环保主题日
        $environmentDay = new HolidayConfig();
        $environmentDay->setName('环保主题日');
        $environmentDay->setHolidayDate(new \DateTimeImmutable('2025-04-22'));
        $environmentDay->setType('special');
        $environmentDay->setDescription('世界地球日，公司环保主题活动');
        $manager->persist($environmentDay);

        // 科技创新日
        $innovationDay = new HolidayConfig();
        $innovationDay->setName('科技创新日');
        $innovationDay->setHolidayDate(new \DateTimeImmutable('2025-05-15'));
        $innovationDay->setType('company');
        $innovationDay->setDescription('公司科技创新展示日，技术分享');
        $manager->persist($innovationDay);

        // 员工健康日
        $healthDay = new HolidayConfig();
        $healthDay->setName('员工健康日');
        $healthDay->setHolidayDate(new \DateTimeImmutable('2025-08-08'));
        $healthDay->setType('company');
        $healthDay->setDescription('员工健康体检和运动活动日');
        $manager->persist($healthDay);

        // 家庭日
        $familyDay = new HolidayConfig();
        $familyDay->setName('家庭日');
        $familyDay->setHolidayDate(new \DateTimeImmutable('2025-06-01'));
        $familyDay->setType('company');
        $familyDay->setDescription('员工家属开放日，亲子活动');
        $manager->persist($familyDay);

        // 冬至节气假期
        $winterSolstice = new HolidayConfig();
        $winterSolstice->setName('冬至节气假期');
        $winterSolstice->setHolidayDate(new \DateTimeImmutable('2025-12-22'));
        $winterSolstice->setType('company');
        $winterSolstice->setDescription('冬至传统节气，下午提前下班');
        $manager->persist($winterSolstice);
    }

    /**
     * @return class-string[]
     */
    public function getDependencies(): array
    {
        return [];
    }
}
