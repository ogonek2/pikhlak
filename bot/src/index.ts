import { createBot } from './bot.js';
import { config } from './config.js';

async function main(): Promise<void> {
  const bot = createBot();

  await bot.api.deleteWebhook({ drop_pending_updates: true });

  const me = await bot.api.getMe();
  console.log(`Pikhlak bot started: @${me.username}`);
  console.log(`Laravel API: ${config.laravelApiUrl}`);
  console.log(`Bot UUID: ${config.botUuid}`);
  console.log('Mode: long polling (dev)');

  await bot.start({
    onStart: () => console.log('Listening for Telegram updates...'),
  });
}

main().catch((error) => {
  console.error('Fatal:', error);
  process.exit(1);
});
