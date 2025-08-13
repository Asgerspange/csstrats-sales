<?php

namespace App\Http\Controllers;

use App\Mail\MassMail;
use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    public function index()
    {
        $recipients = [
            ['email' => 'asgerspange@gmail.com', 'name' => 'Kunde 1'],
            ['email' => 'steamasger@gmail.com', 'name' => 'Kunde 2'],
        ];

        foreach ($recipients as $recipient) {
            Mail::send(new MassMail($recipient['name']));
        }

        return 'Mails have been sent!';
    }
}
