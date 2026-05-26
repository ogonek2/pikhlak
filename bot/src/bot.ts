import { Bot, type Context } from 'grammy';
import { config } from './config.js';
import { LaravelClient } from './laravel/client.js';
import { executeActions } from './telegram/executor.js';

export function createBot(): Bot {
  const bot = new Bot(config.telegramToken);
  const laravel = new LaravelClient();

  bot.catch((err) => {
    console.error('Bot error:', err);
  });

  const handler = async (ctx: Context): Promise<void> => {
    try {
      const result = await laravel.processUpdate(ctx.update);
      const { update_log_id, actions } = result.data;

      if (!actions?.length) {
        return;
      }

      const delivered = await executeActions(ctx.api, actions);
      await laravel.ack(update_log_id, Array.from({ length: delivered }, (_, i) => i));
    } catch (error) {
      console.error('Failed to process update via Laravel:', error);

      const chatId = ctx.chat?.id;
      if (chatId) {
        await ctx.api.sendMessage(
          chatId,
          '⚠️ Сервис временно недоступен. Попробуйте через минуту или напишите менеджеру.',
        );
      }
    }
  };

  bot.on('message', handler);
  bot.on('callback_query', handler);

  return bot;
}
