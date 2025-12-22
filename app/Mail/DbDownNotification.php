<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DbDownNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $error;

    public function __construct($error)
    {
        $this->error = $error;
    }

    public function build()
    {
        return $this->subject('Database Server Down!')
                    ->view('emails.db_down')
                    ->with(['error' => $this->error]);
    }
}
