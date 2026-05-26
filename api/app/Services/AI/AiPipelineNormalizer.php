<?php

namespace App\Services\AI;

class AiPipelineNormalizer
{
    public static function fromJson(?string $json): ?array
    {
        if ($json === null || trim($json) === '') {
            return null;
        }

        $trim = trim($json);
        if (str_contains($trim, '[...]')) {
            return null;
        }

        $decoded = json_decode($trim, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return null;
        }

        return self::fromArray($decoded);
    }

    public static function fromArray(?array $pipeline): ?array
    {
        if (! is_array($pipeline) || $pipeline === []) {
            return null;
        }

        if (isset($pipeline['steps']) && is_array($pipeline['steps'])) {
            $pipeline = $pipeline['steps'];
        }

        if ($pipeline === [] || ! array_is_list($pipeline)) {
            return null;
        }

        return $pipeline;
    }
}
