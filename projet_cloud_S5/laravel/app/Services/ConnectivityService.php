<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ConnectivityService
{
    public function isOnline(): bool
    {
        return Cache::remember('internet_status', 10, function () {
            try {
                $context = stream_context_create([
                    'http' => ['timeout' => 2]
                ]);
                
                $result = @file_get_contents(
                    'https://www.google.com',
                    false,
                    $context
                );
                
                return $result !== false;
            } catch (\Exception $e) {
                return false;
            }
        });
    }
}