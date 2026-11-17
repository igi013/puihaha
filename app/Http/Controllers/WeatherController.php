<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WeatherController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $coordinates = $request->validate(['lat' => ['required', 'numeric', 'between:-90,90'], 'lon' => ['required', 'numeric', 'between:-180,180']]);
        if (! config('services.openweather.key')) {
            return response()->json(['message' => 'Weather has not been configured yet.'], 503);
        }
        $lat = number_format((float) $coordinates['lat'], 3, '.', '');
        $lon = number_format((float) $coordinates['lon'], 3, '.', '');
        try {
            $weather = Cache::remember('weather:'.hash('sha256', $lat.','.$lon), now()->addMinutes(10), function () use ($lat, $lon): array {
                $response = Http::acceptJson()->connectTimeout(3)->timeout(8)->get('https://api.openweathermap.org/data/2.5/weather', [
                    'lat' => $lat, 'lon' => $lon, 'units' => 'metric', 'appid' => config('services.openweather.key'),
                ]);
                $data = $response->json();
                if (! $response->successful() || ! is_array($data) || ! is_numeric(data_get($data, 'main.temp'))) {
                    throw new RuntimeException('Weather provider unavailable.');
                }

                return ['place' => $data['name'] ?? 'Selected location', 'temperature' => $data['main']['temp'], 'description' => data_get($data, 'weather.0.description', 'Not available'), 'humidity' => $data['main']['humidity'] ?? null];
            });
        } catch (ConnectionException|RuntimeException $exception) {
            return response()->json(['message' => 'Weather is currently unavailable. Please try again later.'], 503);
        }

        return response()->json($weather)->header('Cache-Control', 'private, no-store');
    }
}
