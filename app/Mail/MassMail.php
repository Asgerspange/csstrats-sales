<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MassMail extends Mailable
{
    use Queueable, SerializesModels;

    public $recipientName;
    public $recipientEmail;

    public function __construct($recipientName, $recipientEmail = '')
    {
        $this->recipientEmail  = $recipientEmail;
        $this->recipientName = $recipientName;
    }

    public function build()
    {
        return $this->to($this->recipientEmail )
            ->subject('Giv jeres CS2-hold en professionel fordel - 30 dages gratis adgang')
            ->view('emails.massmail')
            ->with([
                'name' => $this->recipientName,
                'senderName' => 'Silas - CsStrats',
                'senderEmail' => 'silas@csstrats.dk'
                    ]);
    }
}
