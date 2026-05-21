<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SendMessageWhatsAppJobs implements ShouldQueue
{
    use Queueable;
    protected $message;
    protected $reciver_phone;

    /**
     * Create a new job instance.
     */
    public function __construct($message, $reciver_phone)
    {
        $this->message = $message;
        $this->reciver_phone = $reciver_phone;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Http::withHeaders([
            'Authorization' => 'WiEgJGbxqbCwMQLCEGxv',
        ])
        ->post('https://api.fonnte.com/send', [
            'target' => $this->reciver_phone,
            'message' => $this->message,
        ]);
    }
}
