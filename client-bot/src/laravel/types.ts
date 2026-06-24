export type BotActionType =
  | 'send_message'
  | 'edit_message'
  | 'delete_message'
  | 'send_media'
  | 'send_media_group'
  | 'answer_callback'
  | 'typing'
  | 'delay';

export interface BotMediaItem {
  type: 'photo' | 'document' | 'video';
  url?: string;
  file_path?: string;
  caption?: string;
}

export interface BotAction {
  type: BotActionType;
  chat_id?: number;
  message_id?: number;
  text?: string;
  parse_mode?: 'HTML' | 'Markdown' | 'MarkdownV2';
  reply_markup?: Record<string, unknown>;
  media?: BotMediaItem[];
  callback_query_id?: string;
  duration?: number;
}

export interface BotUpdateResponse {
  data: {
    update_log_id: number;
    state_version: number;
    actions: BotAction[];
  };
}
