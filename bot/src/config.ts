import 'dotenv/config';

function required(name: string): string {
  const value = process.env[name]?.trim();
  if (!value) {
    throw new Error(`Missing required env: ${name}`);
  }
  return value;
}

export const config = {
  telegramToken: required('TELEGRAM_BOT_TOKEN'),
  laravelApiUrl: (process.env.LARAVEL_API_URL ?? 'http://127.0.0.1:8000').replace(/\/$/, ''),
  botUuid: required('BOT_UUID'),
  hmacSecret: required('PIKHLAK_BOT_HMAC_SECRET'),
};
