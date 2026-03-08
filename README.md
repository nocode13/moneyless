# 💰 WalletAPI — Финтех-платформа электронных кошельков

## Описание проекта

**WalletAPI** — платформа электронных кошельков с переводами между пользователями, мультивалютностью, подпиской, уведомлениями в реальном времени и полной админ-панелью через API.

По сути — упрощённый аналог **Stripe + Revolut**: пользователи пополняют кошелёк, переводят деньги друг другу, конвертируют валюты, оформляют подписки, получают уведомления, а админы контролируют всё через отдельное API.

---

## Архитектура

```
Client (Postman / Frontend)
        │
        ▼
   Laravel API (локально: php artisan serve)
        │
        ├── Redis (Docker — кэш, очереди, rate limiting, broadcasting)
        ├── PostgreSQL (Docker — основная БД)
        ├── Queue Worker (php artisan queue:work)
        └── WebSocket Server (Laravel Reverb)
                └── realtime-уведомления
```

Laravel запускается локально, а **PostgreSQL, Redis и Mailpit** крутятся в Docker Compose.

---

## Модули и покрываемые навыки

> Проект — **монолит**. Все модули живут в одном Laravel-приложении. Уведомления отправляются через встроенные Notifications + Queued Jobs, без отдельного сервиса.

### Модуль 1: Аутентификация и пользователи

**Функционал:**
- Регистрация, логин, логаут
- Email-верификация
- Двухфакторная аутентификация (2FA через TOTP)
- OAuth2 (Google, GitHub)
- Refresh-токены
- Управление устройствами/сессиями (список активных сессий, отзыв)

**Покрываемые навыки Laravel:**
- Laravel Sanctum (API-токены)
- FormRequest (валидация регистрации, логина)
- DTO (CreateUserDTO, LoginDTO)
- Resource (UserResource, SessionResource)
- Events & Listeners (UserRegistered → SendVerificationEmail)
- Notifications (email-верификация, 2FA-код)
- Middleware (auth:sanctum, verified, throttle)
- Rate Limiting (логин — макс. 5 попыток/мин)
- Policy (управление своими сессиями)
- Encryption (хранение 2FA-секретов)

**Модели и связи:**
```
User
├── hasOne: Profile (One to One)
├── hasMany: Session (One to Many)
├── hasMany: Wallet (One to Many)
├── hasMany: Transaction (One to Many)
├── belongsToMany: Role (Many to Many, pivot: role_user)
├── morphMany: AuditLog (Polymorphic One to Many)
└── morphMany: Notification (Polymorphic One to Many)

Profile
└── belongsTo: User

Session
└── belongsTo: User
```

**Таблицы:**
```sql
users: id, email, password, email_verified_at, two_factor_secret, two_factor_enabled, status (active/blocked/pending), timestamps

profiles: id, user_id (FK), first_name, last_name, phone, avatar_path, date_of_birth, country, timezone, timestamps

sessions: id, user_id (FK), token, ip_address, user_agent, device_name, last_activity, expires_at, timestamps
```

**Эндпоинты:**
```
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
POST   /api/v1/auth/refresh
POST   /api/v1/auth/verify-email
POST   /api/v1/auth/forgot-password
POST   /api/v1/auth/reset-password
POST   /api/v1/auth/2fa/enable
POST   /api/v1/auth/2fa/verify
DELETE /api/v1/auth/2fa/disable
GET    /api/v1/auth/sessions
DELETE /api/v1/auth/sessions/{id}
GET    /api/v1/auth/oauth/{provider}/redirect
GET    /api/v1/auth/oauth/{provider}/callback
```

---

### Модуль 2: Кошельки и балансы

**Функционал:**
- Создание кошельков в разных валютах (USD, EUR, UZS, BTC)
- Просмотр баланса
- Пополнение (имитация платёжного шлюза)
- Вывод средств
- Заморозка/разморозка кошелька
- Лимиты на операции

**Покрываемые навыки Laravel:**
- Eloquent (сложные модели с аксессорами/мутаторами)
- Casts (Money cast, Currency enum cast)
- Observers (WalletObserver — логирование изменений баланса)
- Scopes (active wallets, by currency)
- Database Transactions (DB::transaction для атомарности)
- Service Layer (WalletService)
- Repository Pattern (WalletRepository)
- Enum (CurrencyEnum, WalletStatusEnum)
- Value Objects (Money — amount + currency)

**Модели и связи:**
```
Wallet
├── belongsTo: User
├── hasMany: Transaction
├── hasMany: WalletLimit
└── hasOne: WalletSetting

WalletLimit
└── belongsTo: Wallet

Currency
└── hasMany: Wallet
```

**Таблицы:**
```sql
wallets: id, user_id (FK), currency_code, balance (decimal 20,8), status (active/frozen/closed), is_default, timestamps, soft_deletes

wallet_limits: id, wallet_id (FK), operation_type (deposit/withdraw/transfer), daily_limit, monthly_limit, single_limit, timestamps

currencies: code (PK), name, symbol, decimal_places, is_crypto, is_active, exchange_rate_to_usd, timestamps
```

**Эндпоинты:**
```
GET    /api/v1/wallets
POST   /api/v1/wallets
GET    /api/v1/wallets/{id}
GET    /api/v1/wallets/{id}/balance
PATCH  /api/v1/wallets/{id}/freeze
PATCH  /api/v1/wallets/{id}/unfreeze
DELETE /api/v1/wallets/{id}
POST   /api/v1/wallets/{id}/deposit
POST   /api/v1/wallets/{id}/withdraw
GET    /api/v1/currencies
GET    /api/v1/currencies/{code}/rate
```

---

### Модуль 3: Переводы и транзакции

**Функционал:**
- P2P-переводы между пользователями
- Конвертация валют между своими кошельками
- История транзакций с фильтрацией и пагинацией
- Статусы транзакций (pending → processing → completed / failed)
- Комиссии (процент + фиксированная часть)
- Идемпотентность (idempotency key для защиты от дублей)
- Реферальные бонусы при переводах

**Покрываемые навыки Laravel:**
- Database Transactions (критично: перевод = атомарная операция)
- Pessimistic Locking (lockForUpdate при списании)
- Jobs & Queues (ProcessTransfer — асинхронная обработка)
- Events (TransferCompleted, TransferFailed)
- Pipeline Pattern (цепочка проверок перед переводом)
- Strategy Pattern (разные стратегии расчёта комиссий)
- DTOs (CreateTransferDTO, TransactionFilterDTO)
- FormRequest (TransferRequest — валидация суммы, получателя)
- Resource (TransactionResource, TransactionCollection)
- Cursor Pagination (для больших историй транзакций)
- Cache (кэш курсов валют)
- UUID (для idempotency_key и public-facing ID)

**Модели и связи:**
```
Transaction
├── belongsTo: Wallet (sender_wallet)
├── belongsTo: Wallet (receiver_wallet)
├── belongsTo: User (sender)
├── belongsTo: User (receiver)
├── hasOne: Fee
├── hasOne: ExchangeRate (для конвертаций)
├── morphMany: AuditLog
└── belongsTo: Transaction (parent — для связанных транзакций)

Fee
└── belongsTo: Transaction
```

**Таблицы:**
```sql
transactions: id, uuid, idempotency_key (unique), type (transfer/deposit/withdraw/conversion/fee/refund), status (pending/processing/completed/failed/cancelled), sender_wallet_id (FK, nullable), receiver_wallet_id (FK, nullable), sender_id (FK, nullable), receiver_id (FK, nullable), amount (decimal 20,8), currency_code, converted_amount (nullable), converted_currency (nullable), exchange_rate (nullable), description, metadata (json), parent_transaction_id (FK, nullable, self-ref), ip_address, timestamps

fees: id, transaction_id (FK), fee_type (percentage/fixed/mixed), percentage_rate, fixed_amount, total_fee, timestamps
```

**Эндпоинты:**
```
POST   /api/v1/transfers
POST   /api/v1/transfers/preview    (предпросмотр с комиссией)
GET    /api/v1/transactions
GET    /api/v1/transactions/{uuid}
GET    /api/v1/transactions/export   (CSV/PDF экспорт)
POST   /api/v1/conversions           (конвертация между кошельками)
POST   /api/v1/conversions/rate      (текущий курс)
```

**Pipeline перевода:**
```php
// app/Services/TransferPipeline.php
// Каждый шаг — отдельный класс
$pipeline = app(Pipeline::class)
    ->send($transferDTO)
    ->through([
        ValidateIdempotencyKey::class,    // проверка дубликата
        CheckSenderWalletStatus::class,   // кошелёк не заморожен?
        CheckSufficientBalance::class,    // хватает средств?
        CheckTransferLimits::class,       // в пределах лимита?
        CalculateFee::class,              // расчёт комиссии
        CheckReceiverWalletStatus::class, // кошелёк получателя активен?
        CalculateExchangeRate::class,     // конвертация если разные валюты
    ])
    ->thenReturn();
```

---

### Модуль 4: Подписки и тарифные планы

**Функционал:**
- Тарифные планы (Free, Pro, Business)
- Каждый план даёт разные лимиты (кол-во кошельков, лимиты переводов, комиссии)
- Оформление/отмена подписки
- Автопродление (через scheduled job)
- Пробный период (trial)
- Promo-коды

**Покрываемые навыки Laravel:**
- Task Scheduling (ежедневная проверка подписок)
- Jobs (RenewSubscription, ExpireSubscription)
- Eloquent: полиморфные связи (промокоды к разным сущностям)
- FormRequest (SubscribeRequest)
- Gates & Policies (проверка доступа к фичам по плану)
- Middleware (проверка активной подписки)
- Config (тарифные планы в конфиге)

**Модели и связи:**
```
Plan
├── hasMany: PlanFeature
├── hasMany: Subscription
└── hasMany: PromoCode

Subscription
├── belongsTo: User
├── belongsTo: Plan
└── hasMany: SubscriptionPayment

PromoCode
├── belongsTo: Plan (nullable)
├── morphTo: promotable (полиморфная — привязка к плану или глобально)
└── belongsToMany: User (pivot: promo_code_user — кто уже использовал)
```

**Таблицы:**
```sql
plans: id, slug, name, description, price_monthly, price_yearly, currency, max_wallets, max_daily_transfer, transfer_fee_percent, features (json), is_active, sort_order, timestamps

plan_features: id, plan_id (FK), feature_key, feature_value, timestamps

subscriptions: id, user_id (FK), plan_id (FK), status (active/cancelled/expired/trial/past_due), trial_ends_at, current_period_start, current_period_end, cancelled_at, promo_code_id (nullable), timestamps

subscription_payments: id, subscription_id (FK), amount, currency, status, paid_at, timestamps

promo_codes: id, code (unique), discount_type (percentage/fixed), discount_value, max_uses, times_used, valid_from, valid_until, promotable_id, promotable_type, is_active, timestamps

promo_code_user: promo_code_id, user_id, used_at
```

**Эндпоинты:**
```
GET    /api/v1/plans
GET    /api/v1/plans/{slug}
POST   /api/v1/subscriptions
GET    /api/v1/subscriptions/current
PATCH  /api/v1/subscriptions/cancel
PATCH  /api/v1/subscriptions/resume
POST   /api/v1/subscriptions/change-plan
POST   /api/v1/promo-codes/validate
```

---

### Модуль 5: Уведомления и WebSocket

**Функционал:**
- Уведомления по email, в приложении (database), push
- Real-time уведомления через WebSocket
- Настройки уведомлений пользователя (какие каналы для каких событий)
- Пометка прочитанным
- Broadcast событий: перевод получен, статус транзакции изменился, подписка истекает

**Покрываемые навыки Laravel:**
- Notifications (email, database, broadcast каналы)
- Broadcasting (Laravel Reverb + private channels)
- Events (TransferReceived, SubscriptionExpiring, SecurityAlert)
- Listeners (SendTransferNotification, BroadcastTransferEvent)
- Notification Channels (кастомный SMS-канал)
- Queued Notifications
- WebSocket authentication (private channels)

**Модели и связи:**
```
Notification (стандартная Laravel)
├── morphTo: notifiable (User)

NotificationPreference
├── belongsTo: User
```

**Таблицы:**
```sql
notifications: (стандартная Laravel) id (uuid), type, notifiable_type, notifiable_id, data (json), read_at, timestamps

notification_preferences: id, user_id (FK), event_type, email_enabled, database_enabled, push_enabled, sms_enabled, timestamps
```

**Эндпоинты:**
```
GET    /api/v1/notifications
GET    /api/v1/notifications/unread-count
PATCH  /api/v1/notifications/{id}/read
PATCH  /api/v1/notifications/read-all
GET    /api/v1/notification-preferences
PUT    /api/v1/notification-preferences
```

**WebSocket каналы:**
```
private: user.{userId}            — персональные уведомления
private: wallet.{walletId}        — изменения баланса
private: transaction.{uuid}       — статус транзакции
```

---

### Модуль 6: Админ-панель (API)

**Функционал:**
- CRUD пользователей (блокировка, разблокировка)
- Просмотр всех транзакций с фильтрами
- Управление тарифными планами
- Ручной возврат средств (refund)
- Статистика и аналитика (дашборд)
- Управление курсами валют
- Audit log (кто что делал)

**Покрываемые навыки Laravel:**
- Gates & Policies (role-based access)
- Middleware (admin, super-admin)
- Resource (AdminUserResource, AdminTransactionResource — другой набор полей)
- Eloquent: сложные запросы с агрегацией
- Query Builder (статистика — сумма транзакций по дням, топ пользователей)
- Soft Deletes
- Audit Trail (полиморфный AuditLog)
- Export (CSV/PDF генерация)

**Модели и связи:**
```
Role
├── belongsToMany: User (pivot: role_user)
├── belongsToMany: Permission (pivot: permission_role)

Permission
└── belongsToMany: Role

AuditLog
├── morphTo: auditable (User, Transaction, Wallet, Subscription)
├── belongsTo: User (кто совершил действие)
```

**Таблицы:**
```sql
roles: id, name, slug, description, timestamps

permissions: id, name, slug, group, timestamps

role_user: role_id, user_id, timestamps
permission_role: permission_id, role_id, timestamps

audit_logs: id, user_id (FK — кто), auditable_type, auditable_id (полиморф — над чем), action (created/updated/deleted/viewed/exported), old_values (json), new_values (json), ip_address, user_agent, timestamps
```

**Эндпоинты:**
```
# Пользователи
GET    /api/v1/admin/users
GET    /api/v1/admin/users/{id}
PATCH  /api/v1/admin/users/{id}/block
PATCH  /api/v1/admin/users/{id}/unblock
GET    /api/v1/admin/users/{id}/transactions
GET    /api/v1/admin/users/{id}/wallets

# Транзакции
GET    /api/v1/admin/transactions
GET    /api/v1/admin/transactions/{uuid}
POST   /api/v1/admin/transactions/{uuid}/refund
GET    /api/v1/admin/transactions/export

# Планы
POST   /api/v1/admin/plans
PUT    /api/v1/admin/plans/{id}
DELETE /api/v1/admin/plans/{id}

# Промокоды
POST   /api/v1/admin/promo-codes
GET    /api/v1/admin/promo-codes
DELETE /api/v1/admin/promo-codes/{id}

# Валюты
PUT    /api/v1/admin/currencies/{code}
PATCH  /api/v1/admin/currencies/{code}/rate

# Статистика
GET    /api/v1/admin/stats/overview        (общая статистика)
GET    /api/v1/admin/stats/transactions     (график транзакций)
GET    /api/v1/admin/stats/revenue          (доходы от комиссий)
GET    /api/v1/admin/stats/users            (рост пользователей)

# Аудит
GET    /api/v1/admin/audit-logs
GET    /api/v1/admin/audit-logs/{id}
```

---

### Модуль 7: Безопасность и мониторинг

**Функционал:**
- Rate limiting (по IP, по пользователю, по эндпоинту)
- IP Whitelisting для крупных операций
- Логирование всех запросов (HTTP-лог)
- Обнаружение подозрительной активности
- Health check эндпоинт
- Метрики (Prometheus-формат)

**Покрываемые навыки Laravel:**
- Rate Limiting (RateLimiter с разными стратегиями)
- Middleware (LogRequest, DetectSuspiciousActivity, IpWhitelist)
- Scheduler (очистка старых логов, проверка аномалий)
- Cache (Redis — хранение rate limit counters)
- Artisan Commands (кастомные команды для мониторинга)
- Exception Handling (Handler с кастомным рендерингом для API)
- Logging Channels (daily, slack, sentry)

**Эндпоинты:**
```
GET    /api/v1/health
GET    /api/v1/metrics           (Prometheus-формат, для мониторинга)
POST   /api/v1/security/ip-whitelist
DELETE /api/v1/security/ip-whitelist/{ip}
GET    /api/v1/security/activity-log
```

---

## Docker Compose (все сервисы)

```yaml
services:
  # --- PostgreSQL ---
  postgres:
    image: postgres:16
    environment:
      POSTGRES_DB: wallet_api
      POSTGRES_USER: wallet
      POSTGRES_PASSWORD: secret
    volumes:
      - postgres_data:/var/lib/postgresql/data
    ports:
      - "5432:5432"

  # --- Redis ---
  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data

  # --- Mailpit (локальная почта для разработки) ---
  mailpit:
    image: axllent/mailpit
    ports:
      - "1025:1025"
      - "8025:8025"

volumes:
  postgres_data:
  redis_data:
```

**Локально запускаешь:**
```bash
# Терминал 1 — сервер
php artisan serve

# Терминал 2 — очереди
php artisan queue:work redis --tries=3 --backoff=10

# Терминал 3 — WebSocket
php artisan reverb:start

# Терминал 4 — scheduler (если нужен)
php artisan schedule:work

# Терминал 5 — Horizon (мониторинг очередей)
php artisan horizon
```

---

## Тестирование

### Unit-тесты (Pest)
```
tests/Unit/
├── Services/
│   ├── WalletServiceTest.php         — создание, заморозка, баланс
│   ├── TransferServiceTest.php       — перевод, комиссия, конвертация
│   ├── SubscriptionServiceTest.php   — подписка, отмена, продление
│   └── FeeCalculatorTest.php         — расчёт комиссий
├── Models/
│   ├── UserTest.php                  — связи, скоупы
│   ├── WalletTest.php                — кастомные касты, мутаторы
│   └── TransactionTest.php           — статусы, UUID
├── ValueObjects/
│   └── MoneyTest.php                 — арифметика, сравнения
└── Enums/
    ├── CurrencyEnumTest.php
    └── TransactionStatusEnumTest.php
```

### Feature-тесты (Pest)
```
tests/Feature/
├── Auth/
│   ├── RegistrationTest.php          — регистрация, валидация, дубликаты
│   ├── LoginTest.php                 — логин, 2FA, rate limiting
│   ├── EmailVerificationTest.php
│   └── OAuthTest.php
├── Wallet/
│   ├── CreateWalletTest.php          — создание, лимит по плану
│   ├── DepositTest.php               — пополнение, лимиты
│   ├── WithdrawTest.php              — вывод, недостаток средств
│   └── FreezeWalletTest.php
├── Transfer/
│   ├── P2PTransferTest.php           — перевод, атомарность, идемпотентность
│   ├── ConversionTest.php            — конвертация валют
│   ├── TransferLimitsTest.php        — превышение лимитов
│   └── TransferFeeTest.php           — расчёт комиссий
├── Subscription/
│   ├── SubscribeTest.php
│   ├── CancelSubscriptionTest.php
│   └── PromoCodeTest.php
├── Admin/
│   ├── UserManagementTest.php
│   ├── TransactionManagementTest.php
│   ├── RefundTest.php
│   └── StatsTest.php
├── Notification/
│   └── NotificationPreferenceTest.php
└── Security/
    ├── RateLimitTest.php
    └── SuspiciousActivityTest.php
```

### Что покрывают тесты:
- **assertDatabaseHas / assertDatabaseMissing** — проверка записей
- **actingAs** — аутентификация в тестах
- **Mock / Fake** (Queue::fake, Notification::fake, Event::fake)
- **Database transactions in tests** (RefreshDatabase)
- **Factory & Seeder** — генерация тестовых данных
- **HTTP tests** (getJson, postJson, assertStatus, assertJsonStructure)
- **Concurrent transfer tests** — проверка race conditions

---

## Структура проекта

```
wallet-api/
├── docker-compose.yml               ← PostgreSQL, Redis, Mailpit
├── Makefile                         ← удобные команды (make up, make test, make migrate)
├── README.md
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── CheckSubscriptionsCommand.php
│   │       ├── UpdateExchangeRatesCommand.php
│   │       └── CleanupAuditLogsCommand.php
│   ├── DTO/
│   │   ├── Auth/
│   │   │   ├── RegisterDTO.php
│   │   │   └── LoginDTO.php
│   │   ├── Wallet/
│   │   │   ├── CreateWalletDTO.php
│   │   │   └── DepositDTO.php
│   │   ├── Transfer/
│   │   │   ├── CreateTransferDTO.php
│   │   │   └── ConversionDTO.php
│   │   └── Subscription/
│   │       └── SubscribeDTO.php
│   ├── Enums/
│   │   ├── CurrencyEnum.php
│   │   ├── TransactionStatusEnum.php
│   │   ├── TransactionTypeEnum.php
│   │   └── WalletStatusEnum.php
│   ├── Events/
│   │   ├── TransferCompleted.php
│   │   ├── TransferFailed.php
│   │   ├── WalletBalanceChanged.php
│   │   ├── SubscriptionExpiring.php
│   │   └── SuspiciousActivityDetected.php
│   ├── Exceptions/
│   │   ├── InsufficientBalanceException.php
│   │   ├── WalletFrozenException.php
│   │   ├── TransferLimitExceededException.php
│   │   └── DuplicateTransferException.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/V1/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── WalletController.php
│   │   │   │   ├── TransferController.php
│   │   │   │   ├── TransactionController.php
│   │   │   │   ├── SubscriptionController.php
│   │   │   │   ├── NotificationController.php
│   │   │   │   └── SecurityController.php
│   │   │   └── Api/V1/Admin/
│   │   │       ├── AdminUserController.php
│   │   │       ├── AdminTransactionController.php
│   │   │       ├── AdminPlanController.php
│   │   │       ├── AdminPromoCodeController.php
│   │   │       ├── AdminCurrencyController.php
│   │   │       ├── AdminStatsController.php
│   │   │       └── AdminAuditController.php
│   │   ├── Middleware/
│   │   │   ├── AdminMiddleware.php
│   │   │   ├── LogRequestMiddleware.php
│   │   │   ├── CheckSubscriptionMiddleware.php
│   │   │   ├── IpWhitelistMiddleware.php
│   │   │   └── IdempotencyMiddleware.php
│   │   ├── Requests/
│   │   │   ├── Auth/
│   │   │   │   ├── RegisterRequest.php
│   │   │   │   └── LoginRequest.php
│   │   │   ├── Wallet/
│   │   │   │   ├── CreateWalletRequest.php
│   │   │   │   └── DepositRequest.php
│   │   │   ├── Transfer/
│   │   │   │   └── CreateTransferRequest.php
│   │   │   └── Subscription/
│   │   │       └── SubscribeRequest.php
│   │   └── Resources/
│   │       ├── UserResource.php
│   │       ├── WalletResource.php
│   │       ├── TransactionResource.php
│   │       ├── TransactionCollection.php
│   │       ├── PlanResource.php
│   │       ├── SubscriptionResource.php
│   │       ├── NotificationResource.php
│   │       └── Admin/
│   │           ├── AdminUserResource.php
│   │           ├── AdminTransactionResource.php
│   │           └── AdminStatsResource.php
│   ├── Jobs/
│   │   ├── ProcessTransfer.php
│   │   ├── RenewSubscription.php
│   │   ├── ExpireSubscription.php
│   │   ├── UpdateExchangeRates.php
│   │   └── DetectFraud.php
│   ├── Listeners/
│   │   ├── SendTransferNotification.php
│   │   ├── BroadcastBalanceChange.php
│   │   ├── LogTransaction.php
│   │   └── HandleSuspiciousActivity.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Profile.php
│   │   ├── Session.php
│   │   ├── Wallet.php
│   │   ├── WalletLimit.php
│   │   ├── Transaction.php
│   │   ├── Fee.php
│   │   ├── Currency.php
│   │   ├── Plan.php
│   │   ├── PlanFeature.php
│   │   ├── Subscription.php
│   │   ├── SubscriptionPayment.php
│   │   ├── PromoCode.php
│   │   ├── Role.php
│   │   ├── Permission.php
│   │   ├── NotificationPreference.php
│   │   └── AuditLog.php
│   ├── Notifications/
│   │   ├── TransferReceivedNotification.php
│   │   ├── SubscriptionExpiringNotification.php
│   │   ├── SecurityAlertNotification.php
│   │   └── WelcomeNotification.php
│   ├── Observers/
│   │   ├── WalletObserver.php
│   │   └── TransactionObserver.php
│   ├── Pipelines/
│   │   └── Transfer/
│   │       ├── ValidateIdempotencyKey.php
│   │       ├── CheckSenderWalletStatus.php
│   │       ├── CheckSufficientBalance.php
│   │       ├── CheckTransferLimits.php
│   │       ├── CalculateFee.php
│   │       ├── CheckReceiverWalletStatus.php
│   │       └── CalculateExchangeRate.php
│   ├── Policies/
│   │   ├── WalletPolicy.php
│   │   ├── TransactionPolicy.php
│   │   └── SubscriptionPolicy.php
│   ├── Repositories/
│   │   ├── Contracts/
│   │   │   ├── WalletRepositoryInterface.php
│   │   │   └── TransactionRepositoryInterface.php
│   │   ├── WalletRepository.php
│   │   └── TransactionRepository.php
│   ├── Services/
│   │   ├── WalletService.php
│   │   ├── TransferService.php
│   │   ├── SubscriptionService.php
│   │   ├── FeeCalculator.php
│   │   ├── ExchangeRateService.php
│   │   └── AuditService.php
│   └── ValueObjects/
│       └── Money.php
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── routes/
│   ├── api.php
│   ├── api_admin.php
│   └── channels.php
└── tests/
    ├── Unit/
    └── Feature/
```

---

## Полная карта навыков

| Навык | Где используется |
|-------|-----------------|
| **Sanctum** | Модуль 1 — API-токены |
| **FormRequest** | Все модули — валидация входных данных |
| **DTO** | Все модули — типизированный перенос данных |
| **Resource** | Все модули — JSON-трансформация |
| **Eloquent Relationships** | Все типы связей покрыты (см. модели) |
| **Eloquent Scopes** | Модуль 2 — active wallets, by currency |
| **Eloquent Observers** | Модуль 2, 3 — логирование изменений |
| **Eloquent Casts** | Модуль 2 — Money, Enum |
| **Soft Deletes** | Модуль 2, 6 — кошельки, пользователи |
| **Events & Listeners** | Модуль 3, 5 — переводы, уведомления |
| **Jobs & Queues** | Модуль 3, 4, 5 — асинхронная обработка |
| **Notifications** | Модуль 5 — email, database, broadcast |
| **Broadcasting (Reverb)** | Модуль 5 — WebSocket realtime |
| **Pipeline** | Модуль 3 — цепочка проверок перевода |
| **Gates & Policies** | Модуль 4, 6 — авторизация |
| **Middleware** | Модуль 1, 6, 7 — auth, admin, throttle, logging |
| **Rate Limiting** | Модуль 1, 7 — защита от abuse |
| **Task Scheduling** | Модуль 4, 7 — подписки, курсы, очистка |
| **Artisan Commands** | Модуль 7 — кастомные команды |
| **Cache (Redis)** | Модуль 3, 7 — курсы, rate limits |
| **DB Transactions** | Модуль 3 — атомарные переводы |
| **Pessimistic Locking** | Модуль 3 — lockForUpdate |
| **Repository Pattern** | Модуль 2, 3 — абстракция над Eloquent |
| **Service Layer** | Все модули — бизнес-логика |
| **Value Objects** | Модуль 2 — Money |
| **Enums** | Модуль 2, 3 — статусы, типы |
| **UUID** | Модуль 3 — публичные ID транзакций |
| **Cursor Pagination** | Модуль 3 — большие списки транзакций |
| **API Versioning** | Все — /api/v1/ |
| **Exception Handling** | Модуль 3, 7 — кастомные исключения |
| **Factory & Seeder** | Тестирование — тестовые данные |
| **Feature Tests** | Все модули |
| **Unit Tests** | Сервисы, Value Objects, Enums |
| **Docker Compose** | Инфраструктура — PostgreSQL, Redis, Mailpit |
| **PostgreSQL** | Инфраструктура — основная БД |
| **Redis** | Инфраструктура — кэш, очереди, broadcast |
| **Horizon** | Инфраструктура — мониторинг очередей |
| **Health Check** | Модуль 7 — мониторинг |

---

## Рекомендуемый порядок разработки

1. **Неделя 1–2:** Docker + структура + Модуль 1 (Auth)
2. **Неделя 3–4:** Модуль 2 (Кошельки) + Модуль 3 (Переводы)
3. **Неделя 5:** Модуль 4 (Подписки)
4. **Неделя 6:** Модуль 5 (Уведомления + WebSocket)
5. **Неделя 7:** Модуль 6 (Админка)
6. **Неделя 8:** Модуль 7 (Безопасность + мониторинг)
7. **Неделя 9–10:** Тестирование + документация + полировка

---

## Бонусные фичи (для продвинутого уровня)

- **API Documentation** — Swagger/OpenAPI через L5-Swagger
- **GraphQL** — альтернативный API через Lighthouse
- **CQRS** — разделение команд и запросов
- **Event Sourcing** — хранение всех событий кошелька
- **CI/CD** — GitHub Actions (тесты, линтеры, деплой)
- **Feature Flags** — постепенный rollout фич
- **Multi-tenancy** — несколько организаций на одной платформе
- **Микросервис уведомлений** — выделить в отдельное приложение при масштабировании