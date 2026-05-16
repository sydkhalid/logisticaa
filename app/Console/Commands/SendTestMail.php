<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestMail extends Command
{
    protected $signature = 'mail:test {email? : Recipient email address. Defaults to MAIL_FROM_ADDRESS.}';

    protected $description = 'Send a small test email to verify mail transport configuration.';

    public function handle()
    {
        $recipient = $this->argument('email') ?: config('mail.from.address');

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('A valid recipient email address is required.');

            return 1;
        }

        Mail::raw('Logisticaa mail setup test sent at ' . now()->toDateTimeString(), function ($message) use ($recipient) {
            $message->to($recipient)
                ->subject('Logisticaa Mail Test');
        });

        $this->info('Test mail sent to ' . $recipient);

        return 0;
    }
}
