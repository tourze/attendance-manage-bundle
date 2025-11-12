# AttendanceManageBundle

[English](README.md) | [中文](README.zh-CN.md)

一个功能完整的 Symfony 考勤管理 Bundle，为企业提供智能化、多样化的考勤解决方案。

## ✨ 核心功能

### 🕒 考勤管理
- **智能打卡** - 支持 APP、刷卡、WiFi、人脸识别多种打卡方式
- **灵活排班** - 支持固定时间、弹性工作、轮班制等多种排班模式
- **异常处理** - 自动识别迟到、早退、旷工等异常情况并记录原因
- **数据统计** - 提供详细的考勤报表和工作时长统计

### 📊 管理功能
- **请假管理** - 年假、病假、事假等多种请假类型申请与审批
- **加班管理** - 加班申请、加班时长统计与调休管理
- **节假日管理** - 法定节假日、公司特殊假期配置
- **考勤组管理** - 支持不同部门、班次分组管理

### 🎯 技术特性
- **PHPStan Level 8** - 高质量代码标准
- **90%+ 测试覆盖率** - 完整的单元测试和集成测试
- **SOLID 设计** - 遵循面向对象设计原则
- **Symfony 最佳实践** - 完全符合 Symfony Bundle 开发规范

## 🚀 快速开始

### 环境要求

- PHP 8.1+
- Symfony 7.3+
- Doctrine ORM 3.0+

### Composer 安装

```bash
composer require tourze/attendance-manage-bundle
```

### Bundle 注册

```php
// config/bundles.php
return [
    // ...
    Tourze\AttendanceManageBundle\AttendanceManageBundle::class => ['all' => true],
];
```

### 数据库迁移

```bash
# 生成迁移文件
php bin/console doctrine:migrations:diff

# 执行迁移
php bin/console doctrine:migrations:migrate
```

## 📖 使用指南

### 考勤组管理

```php
use Tourze\AttendanceManageBundle\Service\AttendanceGroupService;
use Tourze\AttendanceManageBundle\Service\CheckInService;
use Tourze\AttendanceManageBundle\Enum\CheckInType;

// 创建考勤组
$groupService = $container->get(AttendanceGroupService::class);
$group = $groupService->createGroup(
    '研发部门',
    'flexible',
    ['flexible_minutes' => 30], // 30分钟弹性时间
    [101, 102, 103] // 员工ID列表
);

// 员工打卡
$checkInService = $container->get(CheckInService::class);
$record = $checkInService->checkIn(
    101,
    CheckInType::APP,
    ['location' => ['lat' => 39.9042, 'lng' => 116.4074]]
);
```

### 排班管理

```php
use Tourze\AttendanceManageBundle\Service\WorkShiftService;

$shiftService = $container->get(WorkShiftService::class);

// 创建白班排班
$dayShift = $shiftService->createShift([
    'name' => '白班',
    'start_time' => '09:00',
    'end_time' => '18:00',
    'flexible_minutes' => 15
]);

// 创建夜班排班（跨天）
$nightShift = $shiftService->createShift([
    'name' => '夜班',
    'start_time' => '22:00',
    'end_time' => '06:00',
    'cross_day' => true
]);
```

### 请假申请

```php
use Tourze\AttendanceManageBundle\Service\LeaveApplicationService;

$leaveService = $container->get(LeaveApplicationService::class);

$application = $leaveService->createLeaveApplication(101, [
    'leave_type' => 'annual',
    'start_date' => '2025-08-15',
    'end_date' => '2025-08-17',
    'reason' => '家庭事务'
]);
```

## 🔧 配置选项

### 考勤组类型

| 类型 | 描述 | 适用场景 |
|------|------|----------|
| `fixed` | 固定时间 | 朝九晚五等标准工作时间 |
| `flexible` | 弹性工作 | 弹性工作时间制 |
| `shift` | 轮班制 | 多班次轮换工作 |

### 打卡类型

| 类型 | 说明 | 使用场景 |
|------|--------|------|
| 刷卡打卡 | `CheckInType::CARD` | 传统考勤机刷卡 |
| APP打卡 | `CheckInType::APP` | 手机移动端打卡 |
| WiFi打卡 | `CheckInType::WIFI` | 办公室WiFi范围打卡 |
| 人脸识别 | `CheckInType::FACE` | 人脸识别设备打卡 |

### 考勤状态

| 状态 | 说明 | 自动判断条件 |
|------|------|----------|
| `NORMAL` | 正常 | 正常时间范围内打卡 |
| `LATE` | 迟到 | 上班打卡时间超过规定时间 |
| `EARLY` | 早退 | 下班打卡时间早于规定时间 |
| `ABSENT` | 旷工 | 无打卡记录或未经批准缺勤 |
| `OVERTIME` | 加班 | 超过正常工作时长 |

## 🏗️ 架构设计

### 目录结构

```
src/
├── Entity/          # 实体类
│   ├── AttendanceGroup.php
│   ├── WorkShift.php
│   ├── AttendanceRecord.php
│   └── ...
├── Service/         # 业务服务
│   ├── AttendanceGroupService.php
│   ├── CheckInService.php
│   └── ...
├── Repository/      # 数据仓库
│   ├── AttendanceGroupRepository.php
│   └── ...
├── Interface/       # 接口定义
│   ├── AttendanceGroupServiceInterface.php
│   └── ...
├── Enum/           # 枚举类型
│   ├── AttendanceStatus.php
│   ├── CheckInType.php
│   └── ...
└── Exception/      # 异常处理
    └── AttendanceException.php
```

### 核心服务

- **AttendanceGroupService** - 考勤组管理
- **WorkShiftService** - 排班管理
- **CheckInService** - 打卡服务
- **LeaveApplicationService** - 请假服务
- **OvertimeService** - 加班服务
- **HolidayService** - 节假日服务

## 🧪 质量保证

### 运行测试

```bash
# 运行所有测试
./vendor/bin/phpunit packages/attendance-manage-bundle/tests/

# 运行特定测试
./vendor/bin/phpunit packages/attendance-manage-bundle/tests/Service/AttendanceGroupServiceTest.php

# 生成覆盖率报告
./vendor/bin/phpunit packages/attendance-manage-bundle/tests/ --coverage-html coverage/
```

### 质量指标

- ✅ **100% 单元测试通过** - 核心功能完整测试
- ✅ **90%+ 代码覆盖率** - 高覆盖率的测试保护
- ✅ **100% 类型覆盖** - 强类型约束保护
- ✅ **90%+ 覆盖率** - 综合测试覆盖率

### 代码质量

```bash
# PHPStan 静态分析
./vendor/bin/phpstan analyse packages/attendance-manage-bundle/src/ --level=8

# 代码格式化
./vendor/bin/php-cs-fixer fix packages/attendance-manage-bundle/src/
```

## 📚 API 参考

### 考勤组管理

```php
// 创建考勤组
$group = $groupService->createGroup(string $name, string $type, array $rules, array $memberIds);

// 更新考勤组
$group = $groupService->updateGroup(int $groupId, array $data);

// 分配员工
$groupService->assignEmployees(int $groupId, array $employeeIds);

// 移除员工
$groupService->removeEmployees(int $groupId, array $employeeIds);
```

### 打卡服务

```php
// 上班打卡
$record = $checkInService->checkIn(int $employeeId, CheckInType $type, array $data);

// 下班打卡
$record = $checkInService->checkOut(int $employeeId, CheckInType $type, array $data);

// 获取当日记录
$record = $checkInService->getTodayRecord(int $employeeId);

// 检查是否可以打卡
$canCheckIn = $checkInService->canCheckIn(int $employeeId);
```

### 排班管理

```php
// 创建排班
$shift = $shiftService->createShift(array $shiftData);

// 更新排班
$shift = $shiftService->updateShift(int $shiftId, array $data);

// 检查冲突
$hasConflict = $shiftService->checkShiftConflict(int $groupId, DateTimeInterface $start, DateTimeInterface $end);
```

## ⚙️ 高级配置

### 基础配置

```yaml
# config/packages/attendance_manage.yaml
attendance_manage:
    # 默认规则
    default_rules:
        work_hours: 8
        flexible_minutes: 30
        break_duration: 60

    # 打卡设置
    check_in:
        max_distance: 500  # GPS打卡距离限制(米)
        prevent_duplicate: true  # 防止重复打卡
        location_required: false  # 是否强制要求位置信息

    # 假期配置
    holidays:
        annual_days: 5  # 年假天数
        sick_days: 10   # 病假天数
        personal_days: 3  # 事假天数
```

### 服务扩展

```php
// 自定义服务配置
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('app.custom_attendance_group_service')
        ->class('App\Service\CustomAttendanceGroupService')
        ->arg('$baseService', service('tourze.attendance_manage.attendance_group_service'));
};
```

## 🔌 扩展开发

### 自定义打卡服务

```php
use Tourze\AttendanceManageBundle\Interface\CheckInServiceInterface;

class CustomCheckInService implements CheckInServiceInterface
{
    public function __construct(
        private CheckInServiceInterface $baseService
    ) {}

    public function checkIn(int $employeeId, CheckInType $type, array $data = []): AttendanceRecord
    {
        // 自定义验证逻辑
        if ($type === CheckInType::FACE) {
            $this->validateFaceRecognition($data);
        }

        return $this->baseService->checkIn($employeeId, $type, $data);
    }
}
```

### 自定义规则验证

```php
use Tourze\AttendanceManageBundle\Service\RuleService;

class CustomRuleService extends RuleService
{
    public function validateAttendanceRule(array $rule): bool
    {
        // 继承原有验证
        parent::validateAttendanceRule($rule);

        // 添加自定义验证
        return $this->validateCustomRules($rule);
    }
}
```

## 🤝 贡献指南

我们欢迎所有形式的贡献！请查看 [CONTRIBUTING.md](CONTRIBUTING.md) 了解详细信息。

### 开发流程

1. Fork 本仓库
2. 创建特性分支 (`git checkout -b feature/AmazingFeature`)
3. 运行测试 (`./vendor/bin/phpunit`)
4. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
5. 推送到分支 (`git push origin feature/AmazingFeature`)
6. 创建 Pull Request

### 代码规范

- 遵循 [PSR-12](https://www.php-fig.org/psr/psr-12/) 编码规范
- 保持 PHPStan Level 8 分析通过
- 维持 90%+ 测试覆盖率
- 编写清晰的 PHPDoc 注释

## 📄 许可证

本项目采用 MIT 许可证 - 详情请查看 [LICENSE](LICENSE) 文件

## 📋 更新日志

查看 [CHANGELOG.md](CHANGELOG.md) 了解版本更新信息

## 🆘 技术支持

- 技术邮箱: support@tourze.com
- 问题反馈: [GitHub Issues](https://github.com/tourze/attendance-manage-bundle/issues)
- 技术文档: [官方文档](https://docs.tourze.com/attendance-manage-bundle)

## 🙏 致谢

感谢以下开源项目的支持：

- [Symfony Framework](https://symfony.com/) - 企业级 PHP 框架
- [Doctrine ORM](https://www.doctrine-project.org/) - PHP 数据库持久化层
- [PHPUnit](https://phpunit.de/) - PHP 单元测试框架
- [PHPStan](https://phpstan.org/) - PHP 静态分析工具

---

**AttendanceManageBundle** - 让企业考勤管理更智能、更高效 ❤️