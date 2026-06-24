import { createBot } from './bot.js';
import { config } from './config.js';
import { LaravelClient } from './laravel/client.js';

async function resolveTelegramToken(): Promise<string> {
  if (config.telegramTokenOverride) {
    console.log('Using TELEGRAM_BOT_TOKEN from .env (override)');
    return config.telegramTokenOverride;
  }

  const runtime = await new LaravelClient().fetchRuntimeConfig();

  if (!runtime.is_active) {
    throw new Error('Бот выключен в админке. Включите «Бот активен» и сохраните.');
  }

  if (!runtime.telegram_token) {
    throw new Error('Токен не задан. Укажите его в /admin/client/bot и нажмите «Сохранить».');
  }

  console.log(`Token loaded from Laravel DB (${runtime.name}, type=${runtime.type})`);

  return runtime.telegram_token;
}

async function main(): Promise<void> {
  const token = await resolveTelegramToken();
  const bot = createBot(token);

  await bot.api.deleteWebhook({ drop_pending_updates: true });

  const me = await bot.api.getMe();
  console.log(`Pikhlak CLIENT bot started: @${me.username}`);
  console.log(`Laravel API: ${config.laravelApiUrl}`);
  console.log(`Bot UUID: ${config.botUuid}`);
  console.log('Mode: long polling (dev)');

  await bot.start({
    onStart: () => console.log('Listening for client Telegram updates...'),
  });
}

main().catch((error) => {
  console.error('Fatal:', error);
  process.exit(1);
});
