<?php

namespace App\Console\Commands;

use App\Services\AI\AiProviderFactory;
use Illuminate\Console\Command;

class TestAiProvider extends Command
{
    protected $signature = 'pikhlak:test-ai';

    protected $description = 'Test configured AI provider';

    public function handle(AiProviderFactory $factory): int
    {
        $name = config('ai.default_provider');
        $this->info("Default provider: {$name}");

        foreach (['groq', 'gemini'] as $try) {
            $p = $factory->makeByName($try);
            if (! $p->isConfigured()) {
                $this->line("{$try}: not configured");

                continue;
            }

            foreach ($factory->fallbackModelNames($try) as $modelName) {
                $this->line("Trying {$try} / {$modelName}...");
                try {
                    $result = $p->chat(
                        [['role' => 'user', 'content' => 'Reply with one word: OK']],
                        0.3,
                        30,
                        $modelName
                    );
                    $this->info("OK via {$try}/{$modelName}: ".trim($result['content']));
                    $this->info("Latency: {$result['latency_ms']}ms");

                    return self::SUCCESS;
                } catch (\Throwable $e) {
                    $this->warn(mb_substr($e->getMessage(), 0, 200));
                }
            }
        }

        $provider = $factory->make();
        $this->info('Configured: '.($provider->isConfigured() ? 'yes' : 'no'));

        try {
            $result = $provider->chat(
                [['role' => 'user', 'content' => 'Reply with one word: OK']],
                0.3,
                30
            );
            $this->info('Reply: '.trim($result['content']));
            $this->info("Latency: {$result['latency_ms']}ms");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
