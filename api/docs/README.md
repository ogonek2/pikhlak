# Pikhlak API — Docs

## Database migrations

```bash
cd api
php artisan migrate
```

### Migration map

| File | Tables |
|------|--------|
| `2025_05_22_100001_extend_users_table` | extends `users` |
| `2025_05_22_100002_create_rbac_tables` | roles, permissions, refresh_tokens |
| `2025_05_22_100003_create_projects_and_bots_tables` | projects, bots, bot_webhook_logs, project_user |
| `2025_05_22_100004_create_telegram_tables` | telegram_users, chats, messages, … |
| `2025_05_22_100005_create_crm_tables` | leads, lead_statuses, managers, … |
| `2025_05_22_100006_create_cars_tables` | cars, car_media, … + FK on leads |
| `2025_05_22_100007_create_calculator_tables` | calculator_* |
| `2025_05_22_100008_create_ai_tables` | ai_* |
| `2025_05_22_100009_create_referral_tables` | referral_* |
| `2025_05_22_100010_create_analytics_tables` | analytics_* |
| `2025_05_22_100011_create_system_tables` | settings, integrations, audit_logs |

**Production:** используйте PostgreSQL. В `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pikhlak
DB_USERNAME=...
DB_PASSWORD=...
```

## Admin Panel (Tailwind)

Web UI: **http://127.0.0.1:8000/admin**

| Login | Password |
|-------|----------|
| `admin@pikhlak.local` | `password` |

```bash
# Dev: CSS/JS hot reload (optional, assets already built)
cd api && npm run dev
php artisan serve
```

**Разделы:** дашборд, настройки бота, тексты/кнопки (сразу в Telegram), AI промпты, FAQ, чаты, лиды, статусы pipeline.

Тексты бота хранятся в `settings` (`bot.messages`) и читаются `BotMessageService` при каждом сообщении.

## Seed data

```bash
php artisan db:seed
```

| Item | Value |
|------|-------|
| Admin | `admin@pikhlak.local` / `password` |
| Project slug | `pikhlak` (header `X-Project-Id: 1` after seed) |
| Bot HMAC | set `PIKHLAK_BOT_HMAC_SECRET` in `.env` |

### `.env` (required for API)

```env
JWT_SECRET=   # optional, falls back to APP_KEY
PIKHLAK_BOT_HMAC_SECRET=pikhlak-bot-dev-secret-change-me
```

### Quick test (PowerShell)

```powershell
# Login
$r = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/admin/auth/login" -Method POST -ContentType "application/json" -Body '{"email":"admin@pikhlak.local","password":"password"}'
$token = $r.data.access_token

# Lead statuses
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/admin/lead-statuses" -Headers @{
  Authorization = "Bearer $token"
  "X-Project-Id" = "1"
}
```

## OpenAPI

- Spec: [`openapi.yaml`](./openapi.yaml)
- Preview: [Swagger Editor](https://editor.swagger.io/) → Import file
- Codegen (Vue): `npx openapi-typescript docs/openapi.yaml -o ./types/api.ts`

### API prefixes (planned routes)

| Prefix | Auth |
|--------|------|
| `/api/v1/admin/*` | JWT Bearer + `X-Project-Id` |
| `/api/v1/bot/*` | HMAC `X-Bot-Signature` |
| `/api/v1/webhooks/telegram/{botUuid}` | Telegram secret header |
