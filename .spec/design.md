# 考勤管理Bundle技术设计

## 1. 系统架构

### 1.1 整体架构
```
┌─────────────────────────────────────────────────┐
│                  应用层                          │
│  (Controller / Command / EventListener)         │
└─────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────┐
│                  服务层                          │
│   (AttendanceService / RuleService / ...)       │
└─────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────┐
│                  领域层                          │
│    (Entity / ValueObject / Repository)          │
└─────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────┐
│                  基础设施层                       │
│    (Database / Cache / MessageQueue)            │
└─────────────────────────────────────────────────┘
```

### 1.2 模块划分

```
attendance-manage-bundle/
├── Entity/                    # 实体类
│   ├── AttendanceGroup.php   # 考勤组
│   ├── WorkShift.php         # 班次
│   ├── AttendanceRecord.php  # 考勤记录
│   ├── LeaveApplication.php  # 请假申请
│   ├── OvertimeApplication.php # 加班申请
│   └── HolidayConfig.php     # 节假日配置
├── Service/                   # 业务服务
│   ├── AttendanceService.php # 考勤核心服务
│   ├── RuleService.php       # 规则管理服务
│   ├── CheckInService.php    # 打卡服务
│   ├── ApplicationService.php # 申请服务
│   └── ReportService.php     # 报表服务
├── Repository/               # 数据仓库
├── Event/                    # 事件
├── EventListener/           # 事件监听器
├── Exception/               # 异常类
├── Enum/                    # 枚举类
└── Interface/               # 接口定义
```

## 2. 核心实体设计

### 2.1 考勤组（AttendanceGroup）

```php
class AttendanceGroup
{
    private int $id;
    private string $name;
    private string $type; // fixed|flexible|shift
    private array $rules;
    private array $memberIds;
    private bool $isActive;
    private \DateTimeInterface $createTime;
    private \DateTimeInterface $updateTime;
}
```

### 2.2 班次（WorkShift）

```php
class WorkShift
{
    private int $id;
    private string $name;
    private \DateTimeInterface $startTime;
    private \DateTimeInterface $endTime;
    private ?int $flexibleMinutes;
    private array $breakTimes;
    private bool $crossDay;
    private bool $isActive;
}
```

### 2.3 考勤记录（AttendanceRecord）

```php
class AttendanceRecord
{
    private int $id;
    private int $employeeId;
    private \DateTimeInterface $checkInTime;
    private ?\DateTimeInterface $checkOutTime;
    private string $checkInType; // card|fingerprint|face|app|wifi
    private string $checkInLocation;
    private string $status; // normal|late|early|absent
    private ?string $abnormalReason;
    private \DateTimeInterface $workDate;
}
```

### 2.4 请假申请（LeaveApplication）

```php
class LeaveApplication
{
    private int $id;
    private int $employeeId;
    private string $leaveType; // annual|sick|personal|marriage|maternity
    private \DateTimeInterface $startDate;
    private \DateTimeInterface $endDate;
    private float $duration;
    private string $reason;
    private string $status; // pending|approved|rejected|cancelled
    private ?int $approverId;
    private ?\DateTimeInterface $approveTime;
}
```

## 3. 核心服务设计

### 3.1 考勤服务（AttendanceService）

```php
interface AttendanceServiceInterface
{
    public function checkIn(int $employeeId, CheckInData $data): AttendanceRecord;
    public function checkOut(int $employeeId, CheckOutData $data): AttendanceRecord;
    public function getAttendanceStatus(int $employeeId, \DateTimeInterface $date): AttendanceStatus;
    public function calculateAttendance(int $employeeId, Period $period): AttendanceSummary;
    public function handleAbnormal(int $recordId, string $action): void;
}
```

### 3.2 规则服务（RuleService）

```php
interface RuleServiceInterface
{
    public function createAttendanceGroup(AttendanceGroupData $data): AttendanceGroup;
    public function updateAttendanceGroup(int $groupId, AttendanceGroupData $data): AttendanceGroup;
    public function assignEmployeeToGroup(int $employeeId, int $groupId): void;
    public function getApplicableRules(int $employeeId, \DateTimeInterface $date): RuleSet;
    public function validateAttendance(AttendanceRecord $record, RuleSet $rules): ValidationResult;
}
```

### 3.3 打卡服务（CheckInService）

```php
interface CheckInServiceInterface
{
    public function processCheckIn(CheckInRequest $request): CheckInResult;
    public function validateLocation(LocationData $location): bool;
    public function validateDevice(DeviceData $device): bool;
    public function preventDuplicateCheckIn(int $employeeId, \DateTimeInterface $time): bool;
    public function syncFromDevice(string $deviceId, array $records): void;
}
```

## 4. 数据库设计

### 4.1 主要数据表

```sql
-- 考勤组表
CREATE TABLE attendance_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type ENUM('fixed', 'flexible', 'shift') NOT NULL,
    rules JSON,
    is_active BOOLEAN DEFAULT TRUE,
    create_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    update_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_active (is_active)
);

-- 班次表
CREATE TABLE work_shifts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    group_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    flexible_minutes INT DEFAULT 0,
    break_times JSON,
    cross_day BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    create_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES attendance_groups(id),
    INDEX idx_group (group_id)
);

-- 考勤记录表
CREATE TABLE attendance_records (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    work_date DATE NOT NULL,
    check_in_time DATETIME,
    check_out_time DATETIME,
    check_in_type VARCHAR(20),
    check_in_location VARCHAR(200),
    status ENUM('normal', 'late', 'early', 'absent', 'leave', 'overtime') NOT NULL,
    abnormal_reason VARCHAR(500),
    create_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    update_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_employee_date (employee_id, work_date),
    INDEX idx_employee (employee_id),
    INDEX idx_date (work_date),
    INDEX idx_status (status)
);

-- 请假申请表
CREATE TABLE leave_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    leave_type VARCHAR(20) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    duration DECIMAL(5,2) NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    approver_id INT,
    approve_time DATETIME,
    create_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    update_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee (employee_id),
    INDEX idx_status (status),
    INDEX idx_date_range (start_date, end_date)
);

-- 加班申请表
CREATE TABLE overtime_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    overtime_date DATE NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    duration DECIMAL(5,2) NOT NULL,
    overtime_type ENUM('workday', 'weekend', 'holiday') NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    compensation_type ENUM('paid', 'timeoff') DEFAULT 'paid',
    approver_id INT,
    approve_time DATETIME,
    create_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_employee (employee_id),
    INDEX idx_date (overtime_date),
    INDEX idx_status (status)
);

-- 打卡原始数据表
CREATE TABLE check_in_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    device_id VARCHAR(50),
    check_time DATETIME NOT NULL,
    check_type VARCHAR(20) NOT NULL,
    location_lat DECIMAL(10, 8),
    location_lng DECIMAL(11, 8),
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    create_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_employee_time (employee_id, check_time),
    INDEX idx_device (device_id)
);
```

## 5. 接口设计

### 5.1 RESTful API

```yaml
# 打卡接口
POST /api/attendance/check-in
Request:
  {
    "employee_id": 1001,
    "type": "app",
    "location": {
      "lat": 31.2304,
      "lng": 121.4737
    },
    "device_id": "xxx"
  }
Response:
  {
    "success": true,
    "data": {
      "record_id": 12345,
      "check_time": "2024-01-01 09:00:00",
      "status": "normal"
    }
  }

# 考勤查询接口
GET /api/attendance/records?employee_id=1001&start_date=2024-01-01&end_date=2024-01-31
Response:
  {
    "data": [
      {
        "date": "2024-01-01",
        "check_in": "09:00:00",
        "check_out": "18:00:00",
        "status": "normal"
      }
    ],
    "summary": {
      "work_days": 22,
      "late_count": 2,
      "early_count": 1,
      "absent_count": 0
    }
  }

# 请假申请接口
POST /api/attendance/leave-application
Request:
  {
    "employee_id": 1001,
    "leave_type": "annual",
    "start_date": "2024-01-15",
    "end_date": "2024-01-16",
    "reason": "个人事务"
  }
```

### 5.2 事件接口

```php
// 打卡成功事件
class CheckInSuccessEvent
{
    public function __construct(
        private AttendanceRecord $record,
        private EmployeeInterface $employee
    ) {}
}

// 考勤异常事件
class AttendanceAbnormalEvent
{
    public function __construct(
        private AttendanceRecord $record,
        private string $abnormalType,
        private EmployeeInterface $employee
    ) {}
}

// 申请审批事件
class ApplicationApprovedEvent
{
    public function __construct(
        private ApplicationInterface $application,
        private ApproverInterface $approver
    ) {}
}
```

## 6. 缓存策略

### 6.1 缓存方案

```php
// 考勤规则缓存
class RuleCache
{
    private const TTL = 3600; // 1小时
    private const KEY_PREFIX = 'attendance:rule:';
    
    public function get(int $employeeId): ?RuleSet
    {
        $key = self::KEY_PREFIX . $employeeId;
        return $this->cache->get($key);
    }
    
    public function set(int $employeeId, RuleSet $rules): void
    {
        $key = self::KEY_PREFIX . $employeeId;
        $this->cache->set($key, $rules, self::TTL);
    }
}

// 考勤记录缓存
class RecordCache
{
    private const TTL = 300; // 5分钟
    private const KEY_PREFIX = 'attendance:record:';
    
    public function getTodayRecord(int $employeeId): ?AttendanceRecord
    {
        $key = self::KEY_PREFIX . $employeeId . ':' . date('Y-m-d');
        return $this->cache->get($key);
    }
}
```

## 7. 性能优化

### 7.1 查询优化
- 使用索引优化查询
- 分页查询大数据集
- 使用读写分离

### 7.2 批量处理
- 批量导入打卡数据
- 批量计算考勤统计
- 使用消息队列异步处理

### 7.3 缓存优化
- 热点数据缓存
- 查询结果缓存
- 分布式缓存

## 8. 安全设计

### 8.1 数据安全
- 敏感数据加密存储
- 打卡数据防篡改（数字签名）
- 操作日志审计

### 8.2 访问控制
- 基于角色的权限控制（RBAC）
- API接口认证（JWT）
- 防重放攻击

## 9. 扩展机制

### 9.1 打卡方式扩展

```php
interface CheckInMethodInterface
{
    public function validate(CheckInRequest $request): bool;
    public function process(CheckInRequest $request): CheckInResult;
    public function getType(): string;
}

// 注册新的打卡方式
class CheckInMethodRegistry
{
    private array $methods = [];
    
    public function register(CheckInMethodInterface $method): void
    {
        $this->methods[$method->getType()] = $method;
    }
}
```

### 9.2 规则扩展

```php
interface AttendanceRuleInterface
{
    public function validate(AttendanceRecord $record): ValidationResult;
    public function getPriority(): int;
    public function getName(): string;
}

// 自定义规则示例
class FlexibleTimeRule implements AttendanceRuleInterface
{
    public function validate(AttendanceRecord $record): ValidationResult
    {
        // 弹性工时验证逻辑
    }
}
```

## 10. 测试策略

### 10.1 单元测试
- 服务层测试覆盖率≥90%
- 实体业务逻辑测试
- 工具类测试

### 10.2 集成测试
- API接口测试
- 数据库操作测试
- 缓存操作测试

### 10.3 性能测试
- 并发打卡测试
- 大数据量查询测试
- 缓存命中率测试

## 11. 部署架构

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Nginx     │────▶│   PHP-FPM   │────▶│   MySQL     │
└─────────────┘     └─────────────┘     └─────────────┘
                            │                    │
                            ▼                    │
                    ┌─────────────┐              │
                    │    Redis    │◀─────────────┘
                    └─────────────┘
                            │
                            ▼
                    ┌─────────────┐
                    │  RabbitMQ   │
                    └─────────────┘
```

## 12. 监控指标

### 12.1 业务指标
- 日打卡成功率
- 异常考勤占比
- 申请审批时效

### 12.2 技术指标
- API响应时间
- 数据库查询耗时
- 缓存命中率
- 消息队列积压数

## 13. 技术选型理由

### 13.1 Symfony Framework
- 成熟的企业级框架
- 优秀的Bundle机制
- 强大的依赖注入容器
- 完善的事件系统

### 13.2 Doctrine ORM
- 强大的对象映射
- 查询构建器
- 数据库迁移工具
- 二级缓存支持

### 13.3 Redis
- 高性能缓存
- 分布式锁
- 消息队列功能
- 持久化支持

### 13.4 RabbitMQ
- 可靠的消息传递
- 灵活的路由
- 高可用集群
- 监控管理界面