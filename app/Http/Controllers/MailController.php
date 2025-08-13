<?php

namespace App\Http\Controllers;

use App\Mail\MassMail;
use Illuminate\Support\Facades\Mail;
use League\Csv\Reader;
class MailController extends Controller
{
    public function index()
    {
        $file = storage_path('emails.csv'); // CSV filen
        $csv = Reader::createFromPath($file, 'r');
        $csv->setHeaderOffset(0); // Brug første række som header

        $records = $csv->getRecords();
        foreach ($records as $record) {
            if (trim($record['Status']) === 'Blank') {
                $name = $record['Skole'];
                $email = $record['Note'];

                Mail::send(new MassMail($name, 'asgerspange@gmail.com'));
            }
        }

        return 'Mails er sendt til alle med status Blank';
    }
}
