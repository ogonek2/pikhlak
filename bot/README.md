# Pikhlak Telegram Bot

Lightweight transport: Telegram ↔ Laravel API (headless).

## Flow

```
Telegram → grammY (this service) → POST /api/v1/bot/updates → Laravel
Laravel → { actions: [...] } → grammY executor → Telegram
```

## Setup

```bash
cd bot
npm install
cp .env.example .env   # fill TELEGRAM_BOT_TOKEN, BOT_UUID, secrets
```

`BOT_UUID` — from `php artisan db:seed` output or `bots` table.

## Run (dev)

**Terminal 1** — Laravel API:

```bash
cd api
php artisan serve
```

**Terminal 2** — Bot:

```bash
cd bot
npm run dev
```

## Production

Use webhook on Laravel or bot service with public HTTPS URL. For local dev — **long polling** (`npm start`).

## Env

| Variable | Description |
|----------|-------------|
| `TELEGRAM_BOT_TOKEN` | From @BotFather |
| `LARAVEL_API_URL` | e.g. `http://127.0.0.1:8000` |
| `BOT_UUID` | UUID from `bots` table |
| `PIKHLAK_BOT_HMAC_SECRET` | Same as in `api/.env` |
