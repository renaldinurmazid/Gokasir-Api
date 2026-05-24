<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SendTelegramMessageJobs implements ShouldQueue
{
    use Queueable;

    protected string $text;

    /**
     * Create a new job instance.
     */
    public function __construct($text)
    {
        $this->text = $text;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Http::post("https://api.telegram.org/bot7219922547:AAEIoouX8l9ANKh-Rw54OHoZY05qxQLQlYY/sendMessage", [
            'chat_id' => '-4698105870',
            'text'    => $this->text,
            'parse_mode' => 'Markdown',
        ]);
    }
}
