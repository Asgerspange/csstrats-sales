<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FetchCurrencyRates extends Command
{
    protected $signature = 'currency:fetch';
    protected $description = 'Fetch EUR and USD to DKK rates and save to JSON file';

    public function handle()
    {
        ini_set('max_execution_time', 3000);
        $currencies = ['USD', 'EUR'];
        $target = 'DKK';
        $rates = [];

        foreach ($currencies as $base) {
            $url = "https://open.er-api.com/v6/latest/{$base}";
            $response = Http::get($url);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['rates'][$target])) {
                    $rates[$base] = $data['rates'][$target];
                }
            }
        }

        if (!empty($rates)) {
            Storage::disk('local')->put('currency_rates.json', json_encode($rates, JSON_PRETTY_PRINT));
            $this->info('Currency rates updated successfully: ' . json_encode($rates));
        } else {
            $this->error('Failed to fetch currency rates.');
        }
    }
}
