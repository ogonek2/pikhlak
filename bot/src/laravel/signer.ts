import { createHmac } from 'node:crypto';

export function signBody(secret: string, body: string, timestamp?: number): {
  timestamp: number;
  signature: string;
} {
  const ts = timestamp ?? Math.floor(Date.now() / 1000);
  const signature = createHmac('sha256', secret)
    .update(`${ts}.${body}`)
    .digest('hex');

  return { timestamp: ts, signature };
}
