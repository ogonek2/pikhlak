import { config } from '../config.js';
import { signBody } from './signer.js';
import type { BotUpdateResponse } from './types.js';

export class LaravelClient {
  async processUpdate(update: unknown): Promise<BotUpdateResponse> {
    const body = JSON.stringify({
      update,
      received_at: new Date().toISOString(),
    });

    const { timestamp, signature } = signBody(config.hmacSecret, body);

    const response = await fetch(`${config.laravelApiUrl}/api/v1/bot/updates`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Bot-Id': config.botUuid,
        'X-Bot-Timestamp': String(timestamp),
        'X-Bot-Signature': signature,
      },
      body,
    });

    if (!response.ok) {
      const text = await response.text();
      throw new Error(`Laravel API ${response.status}: ${text}`);
    }

    return (await response.json()) as BotUpdateResponse;
  }

  async ack(updateLogId: number, deliveredActions: number[]): Promise<void> {
    const body = JSON.stringify({ delivered_actions: deliveredActions });
    const { timestamp, signature } = signBody(config.hmacSecret, body);

    await fetch(`${config.laravelApiUrl}/api/v1/bot/updates/${updateLogId}/ack`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Bot-Id': config.botUuid,
        'X-Bot-Timestamp': String(timestamp),
        'X-Bot-Signature': signature,
      },
      body,
    });
  }
}
