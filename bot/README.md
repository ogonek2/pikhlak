# Pikhlak Telegram Bot (прогрев лидов)

Lightweight transport: Telegram ↔ Laravel API (headless).

> Клиентский бот — отдельный проект: [`../client-bot/`](../client-bot/)

## Flow

```
Telegram → grammY (bot/) → POST /api/v1/bot/updates → Laravel BotDispatcher
Laravel → { actions: [...] } → grammY executor → Telegram
```

## Setup

```bash
cd bot
npm install
cp .env.example .env   # TELEGRAM_BOT_TOKEN, BOT_UUID (warming), secrets
```

`BOT_UUID` — из таблицы `bots` where `type=warming`.

## Run (dev)

```bash
cd api && php artisan serve
cd bot && npm run dev
```

## Env

| Variable | Description |
|----------|-------------|
| `TELEGRAM_BOT_TOKEN` | From @BotFather (warming bot) |
| `LARAVEL_API_URL` | e.g. `http://127.0.0.1:8000` |
| `BOT_UUID` | UUID warming bot from `bots` table |
| `PIKHLAK_BOT_HMAC_SECRET` | Same as in `api/.env` |
