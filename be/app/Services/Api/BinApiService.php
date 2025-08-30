<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Services\Api\Exceptions\ApiRateLimitException;
use App\Services\Api\Exceptions\ApiTimeoutException;
use App\Services\Api\Exceptions\ApiUnavailableException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BinApiService
{
    private Client $client;
    private string $baseUrl;
    private int $timeout;
    private int $retryAttempts;
    private int $rateLimitDelay;

    public function __construct(?Client $client = null)
    {
        $this->baseUrl = config('services.bin_api.base_url', 'https://lookup.binlist.net');
        $this->timeout = (int) config('services.bin_api.timeout', 30);
        $this->retryAttempts = (int) config('services.bin_api.retry_attempts', 3);
        $this->rateLimitDelay = (int) config('services.bin_api.rate_limit_delay', 1000);

        $this->client = $client ?: new Client([
            'timeout' => $this->timeout,
            'connect_timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'BinLookupSystem/1.0',
            ],
        ]);
    }

    public function lookupBin(string $bin): array
    {
        $cacheKey = "bin_lookup:{$bin}";
        
        return Cache::remember($cacheKey, 3600, function () use ($bin) {
            return $this->performLookup($bin);
        });
    }

    private function performLookup(string $bin): array
    {
        $url = "{$this->baseUrl}/{$bin}";
        $attempts = 0;

        while ($attempts < $this->retryAttempts) {
            try {
                $this->respectRateLimit();
                
                $response = $this->client->get($url);
                $data = json_decode($response->getBody()->getContents(), true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('Invalid JSON response from BIN API');
                }

                Log::debug('BIN API lookup successful', [
                    'bin' => $bin,
                    'attempts' => $attempts + 1,
                ]);

                return $this->normalizeResponse($data);

            } catch (ClientException $e) {
                $this->handleClientException($e, $bin, $attempts);
            } catch (ServerException $e) {
                $this->handleServerException($e, $bin, $attempts);
            } catch (ConnectException $e) {
                $this->handleConnectionException($e, $bin, $attempts);
            } catch (RequestException $e) {
                $this->handleGenericException($e, $bin, $attempts);
            }

            $attempts++;
            
            if ($attempts < $this->retryAttempts) {
                $delay = $this->calculateBackoffDelay($attempts);
                Log::info("Retrying BIN lookup in {$delay}ms", [
                    'bin' => $bin,
                    'attempt' => $attempts + 1,
                    'max_attempts' => $this->retryAttempts,
                ]);
                usleep($delay * 1000);
            }
        }

        throw new ApiUnavailableException("Failed to lookup BIN {$bin} after {$this->retryAttempts} attempts");
    }

    private function respectRateLimit(): void
    {
        static $lastRequestTime = 0;
        $currentTime = microtime(true) * 1000;
        $timeSinceLastRequest = $currentTime - $lastRequestTime;
        
        if ($timeSinceLastRequest < $this->rateLimitDelay) {
            $sleepTime = $this->rateLimitDelay - $timeSinceLastRequest;
            usleep((int)($sleepTime * 1000));
        }
        
        $lastRequestTime = microtime(true) * 1000;
    }

    private function normalizeResponse(array $data): array
    {
        return [
            'bank_name' => $data['bank']['name'] ?? null,
            'card_type' => $data['type'] ?? null,
            'card_brand' => $data['brand'] ?? $data['scheme'] ?? null,
            'country_code' => $data['country']['alpha2'] ?? null,
            'country_name' => $data['country']['name'] ?? null,
            'website' => $data['bank']['url'] ?? null,
            'phone' => $data['bank']['phone'] ?? null,
            'raw_response' => $data,
        ];
    }

    private function handleClientException(ClientException $e, string $bin, int $attempts): void
    {
        $statusCode = $e->getResponse()->getStatusCode();
        
        if ($statusCode === 429) {
            Log::warning('BIN API rate limit hit', [
                'bin' => $bin,
                'attempt' => $attempts + 1,
            ]);
            throw new ApiRateLimitException('Rate limit exceeded for BIN API');
        }

        if ($statusCode === 404) {
            Log::info('BIN not found in API', ['bin' => $bin]);
            throw new \RuntimeException("BIN {$bin} not found");
        }

        Log::error('BIN API client error', [
            'bin' => $bin,
            'status_code' => $statusCode,
            'error' => $e->getMessage(),
            'attempt' => $attempts + 1,
        ]);

        throw new \RuntimeException("Client error: {$e->getMessage()}", $statusCode);
    }

    private function handleServerException(ServerException $e, string $bin, int $attempts): void
    {
        $statusCode = $e->getResponse()->getStatusCode();
        
        Log::error('BIN API server error', [
            'bin' => $bin,
            'status_code' => $statusCode,
            'error' => $e->getMessage(),
            'attempt' => $attempts + 1,
        ]);

        // Server errors are retryable
        if ($attempts >= $this->retryAttempts - 1) {
            throw new ApiUnavailableException("Server error: {$e->getMessage()}", $statusCode);
        }
    }

    private function handleConnectionException(ConnectException $e, string $bin, int $attempts): void
    {
        Log::error('BIN API connection error', [
            'bin' => $bin,
            'error' => $e->getMessage(),
            'attempt' => $attempts + 1,
        ]);

        // Connection errors are retryable
        if ($attempts >= $this->retryAttempts - 1) {
            throw new ApiTimeoutException("Connection error: {$e->getMessage()}");
        }
    }

    private function handleGenericException(RequestException $e, string $bin, int $attempts): void
    {
        Log::error('BIN API generic error', [
            'bin' => $bin,
            'error' => $e->getMessage(),
            'attempt' => $attempts + 1,
        ]);

        if ($attempts >= $this->retryAttempts - 1) {
            throw new \RuntimeException("Request error: {$e->getMessage()}");
        }
    }

    private function calculateBackoffDelay(int $attempts): int
    {
        return min(pow(2, $attempts) * $this->rateLimitDelay, 60000); // Max 60s
    }

    public function healthCheck(): bool
    {
        try {
            $response = $this->client->get($this->baseUrl, ['timeout' => 5]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            Log::warning('BIN API health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}