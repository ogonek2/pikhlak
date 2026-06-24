# Pikhlak Client Bot

Telegram-транспорт для **клиентского** бота (аренда, платежи, ТО, страховка).

Отдельный сервис от `bot/` (прогрев лидов). Общая БД и админка — в `api/`.

## Архитектура

```
Telegram → grammY (client-bot/) → POST /api/v1/bot/updates → Laravel ClientBotDispatcher
Laravel → { actions } → executor → Telegram

Проактивные уведомления (PDF, QR): Laravel Scheduler в api/ → Telegram API напрямую
```

| Папка | Роль |
|-------|------|
| `client-bot/` | Long-polling / webhook transport |
| `api/` | Логика, CRM, счета, дашборд `/admin/client/bot` |
| БД `bots` | `type=client`, токен, UUID |

## Setup

```bash
cd client-bot
npm install
cp .env.example .env
```

Заполните `.env` (токен Telegram **не нужен** — берётся из админки):

| Variable | Где взять |
|----------|-----------|
| `BOT_UUID` | Карточка бота в админке (`bots.uuid`, type=client) |
| `PIKHLAK_BOT_HMAC_SECRET` | `api/.env` — тот же секрет |
| `LARAVEL_API_URL` | `http://127.0.0.1:8000` |
| `TELEGRAM_BOT_TOKEN` | Опционально, только для локального override |

Токен задаётся в `/admin/client/bot` → сервис запрашивает `GET /api/v1/bot/health-config`.

## Run (dev)

```bash
# Terminal 1
cd api && php artisan serve

# Terminal 2 — этот сервис
cd client-bot && npm run dev

# Terminal 3 — планировщик уведомлений
cd api && php artisan schedule:work
```

## Production

Webhook на Laravel или long-polling этого сервиса с публичным HTTPS.
