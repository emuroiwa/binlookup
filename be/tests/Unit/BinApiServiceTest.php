<?php

use App\Services\Api\BinApiService;
use App\Services\Api\Exceptions\ApiRateLimitException;
use App\Services\Api\Exceptions\ApiTimeoutException;
use App\Services\Api\Exceptions\ApiUnavailableException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

describe("BinApiService", function () {
    beforeEach(function () {
        $this->client = Mockery::mock(Client::class);
        $this->service = new BinApiService($this->client);
        
        Log::shouldReceive("debug")->byDefault();
        Log::shouldReceive("info")->byDefault();
        Log::shouldReceive("warning")->byDefault();
        Log::shouldReceive("error")->byDefault();
    });

    describe("lookupBin", function () {
        it("returns cached result when available", function () {
            $bin = "123456";
            $cachedData = ["bank_name" => "Cached Bank"];

            Cache::shouldReceive("remember")
                ->once()
                ->with("bin_lookup:{$bin}", 3600, Mockery::type("Closure"))
                ->andReturn($cachedData);

            $result = $this->service->lookupBin($bin);

            expect($result)->toBe($cachedData);
        });

        it("performs API lookup when not cached", function () {
            $bin = "123456";
            $responseData = [
                "bank" => ["name" => "Test Bank"],
                "type" => "debit",
                "brand" => "visa",
                "country" => ["alpha2" => "US", "name" => "United States"]
            ];

            $response = new Response(200, [], json_encode($responseData));

            Cache::shouldReceive("remember")
                ->once()
                ->andReturnUsing(function ($key, $ttl, $callback) {
                    return $callback();
                });

            $this->client
                ->shouldReceive("get")
                ->once()
                ->with("https://lookup.binlist.net/{$bin}")
                ->andReturn($response);

            $result = $this->service->lookupBin($bin);

            expect($result)->toHaveKey("bank_name", "Test Bank");
            expect($result)->toHaveKey("card_type", "debit");
            expect($result)->toHaveKey("card_brand", "visa");
        });

        it("handles rate limit exceptions", function () {
            $bin = "123456";
            $request = new Request("GET", "test");
            $response = new Response(429);
            $exception = new ClientException("Too Many Requests", $request, $response);

            Cache::shouldReceive("remember")
                ->once()
                ->andReturnUsing(function ($key, $ttl, $callback) {
                    return $callback();
                });

            $this->client
                ->shouldReceive("get")
                ->once()
                ->andThrow($exception);

            expect(fn() => $this->service->lookupBin($bin))
                ->toThrow(ApiRateLimitException::class, "Rate limit exceeded for BIN API");
        });

        it("handles server errors with retry", function () {
            $bin = "123456";
            $request = new Request("GET", "test");
            $response = new Response(500);
            $serverException = new ServerException("Internal Server Error", $request, $response);

            $retryResponse = new Response(200, [], json_encode([
                "bank" => ["name" => "Test Bank"],
                "type" => "debit"
            ]));

            Cache::shouldReceive("remember")
                ->once()
                ->andReturnUsing(function ($key, $ttl, $callback) {
                    return $callback();
                });

            $this->client
                ->shouldReceive("get")
                ->once()
                ->andThrow($serverException);

            $this->client
                ->shouldReceive("get")
                ->once()
                ->andReturn($retryResponse);

            $result = $this->service->lookupBin($bin);

            expect($result)->toHaveKey("bank_name", "Test Bank");
        });

        it("throws unavailable exception after max retries", function () {
            $bin = "123456";
            $request = new Request("GET", "test");
            $response = new Response(500);
            $exception = new ServerException("Server Error", $request, $response);

            Cache::shouldReceive("remember")
                ->once()
                ->andReturnUsing(function ($key, $ttl, $callback) {
                    return $callback();
                });

            $this->client
                ->shouldReceive("get")
                ->times(3)
                ->andThrow($exception);

            expect(fn() => $this->service->lookupBin($bin))
                ->toThrow(ApiUnavailableException::class);
        });
    });

    describe("healthCheck", function () {
        it("returns true when API is healthy", function () {
            $response = new Response(200);

            $this->client
                ->shouldReceive("get")
                ->once()
                ->with("https://lookup.binlist.net", ["timeout" => 5])
                ->andReturn($response);

            $result = $this->service->healthCheck();

            expect($result)->toBeTrue();
        });

        it("returns false when API is unhealthy", function () {
            $this->client
                ->shouldReceive("get")
                ->once()
                ->andThrow(new Exception("Connection failed"));

            $result = $this->service->healthCheck();

            expect($result)->toBeFalse();
        });
    });
});
