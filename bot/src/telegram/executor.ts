import { Api, InputFile, InputMediaBuilder, InlineKeyboard, Keyboard } from 'grammy';
import { existsSync } from 'node:fs';
import type { BotAction, BotMediaItem } from '../laravel/types.js';

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function buildReplyMarkup(markup?: Record<string, unknown>) {
  if (!markup) return undefined;

  if (Array.isArray(markup.inline_keyboard)) {
    return InlineKeyboard.from(
      (markup.inline_keyboard as Array<Array<{ text: string; callback_data?: string; url?: string }>>).map(
        (row) =>
          row.map((btn) => {
            if (btn.url) return InlineKeyboard.url(btn.text, btn.url);
            return InlineKeyboard.text(btn.text, btn.callback_data ?? btn.text);
          }),
      ),
    );
  }

  if (Array.isArray(markup.keyboard)) {
    const keyboard = Keyboard.from(
      (markup.keyboard as Array<Array<{ text: string }>>).map((row) => row.map((b) => b.text)),
    );
    if (markup.resize_keyboard) keyboard.resized();
    if (markup.one_time_keyboard) keyboard.oneTime();
    return keyboard;
  }

  return undefined;
}

async function sendMediaItem(api: Api, chatId: number, item: BotMediaItem): Promise<void> {
  const caption = item.caption;
  const opts = caption ? { caption, parse_mode: 'HTML' as const } : {};

  if (item.type === 'photo') {
    if (item.file_path && existsSync(item.file_path)) {
      await api.sendPhoto(chatId, new InputFile(item.file_path), opts);
      return;
    }
    if (item.url) {
      await api.sendPhoto(chatId, item.url, opts);
      return;
    }
  }

  if (item.type === 'document' && item.file_path && existsSync(item.file_path)) {
    await api.sendDocument(chatId, new InputFile(item.file_path), opts);
    return;
  }

  if (item.type === 'video' && item.url) {
    await api.sendVideo(chatId, item.url, opts);
    return;
  }

  throw new Error(`Cannot send media: missing file_path or url (${item.type})`);
}

export async function executeActions(api: Api, actions: BotAction[]): Promise<number> {
  let delivered = 0;

  for (const [index, action] of actions.entries()) {
    try {
      switch (action.type) {
        case 'typing': {
          if (action.chat_id) {
            await api.sendChatAction(action.chat_id, 'typing');
          }
          if (action.duration) {
            await sleep(action.duration * 1000);
          }
          break;
        }
        case 'delay': {
          await sleep((action.duration ?? 1) * 1000);
          break;
        }
        case 'send_message': {
          if (!action.chat_id || !action.text) break;
          await api.sendMessage(action.chat_id, action.text, {
            parse_mode: action.parse_mode,
            reply_markup: buildReplyMarkup(action.reply_markup),
          });
          break;
        }
        case 'edit_message': {
          if (!action.chat_id || !action.message_id || !action.text) break;
          await api.editMessageText(action.chat_id, action.message_id, action.text, {
            parse_mode: action.parse_mode,
            reply_markup: buildReplyMarkup(action.reply_markup),
          });
          break;
        }
        case 'delete_message': {
          if (!action.chat_id || !action.message_id) break;
          await api.deleteMessage(action.chat_id, action.message_id);
          break;
        }
        case 'answer_callback': {
          if (!action.callback_query_id) break;
          await api.answerCallbackQuery(action.callback_query_id, {
            text: action.text,
          });
          break;
        }
        case 'send_media': {
          if (!action.chat_id || !action.media?.length) break;
          for (const item of action.media) {
            try {
              await sendMediaItem(api, action.chat_id, item);
            } catch (mediaError) {
              console.error('Media item failed, fallback to text:', mediaError);
              if (item.caption && action.chat_id) {
                await api.sendMessage(action.chat_id, item.caption, { parse_mode: 'HTML' });
              }
            }
          }
          break;
        }
        case 'send_media_group': {
          if (!action.chat_id || !action.media?.length) break;
          try {
            const album = action.media.map((item, index) => {
              const source =
                item.file_path && existsSync(item.file_path)
                  ? new InputFile(item.file_path)
                  : item.url ?? '';
              const caption = index === 0 ? item.caption : undefined;
              return InputMediaBuilder.photo(source, {
                caption,
                parse_mode: caption ? 'HTML' : undefined,
              });
            });
            await api.sendMediaGroup(action.chat_id, album);
          } catch (mediaError) {
            console.error('Media group failed:', mediaError);
            const fallback = action.media[0]?.caption;
            if (fallback) {
              await api.sendMessage(action.chat_id, fallback, { parse_mode: 'HTML' });
            }
          }
          break;
        }
        default:
          console.warn(`Unknown action type: ${action.type}`);
      }
      delivered = index + 1;
    } catch (error) {
      console.error(`Action #${index} failed (${action.type}):`, error);
      throw error;
    }
  }

  return delivered;
}
