<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Eamirgh\SnappPay\Contracts\TokenManagerInterface;
use Eamirgh\SnappPay\Exceptions\SnappPayAuthException;

class TokenManager implements TokenManagerInterface
{
    protected ?string $memoryToken = null;
    protected ?int $memoryTokenExpiry = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        protected array $config,
        protected ?CacheRepository $cache = null
    ) {
    }

    /**
     * Retrieve a valid Bearer access token.
     *
     * @throws SnappPayAuthException
     */
    public function getToken(bool $forceFresh = false): string
    {
        if (!$forceFresh) {
            // 1. In-memory cache for current request lifecycle
            if ($this->memoryToken !== null && $this->memoryTokenExpiry !== null && time() < $this->memoryTokenExpiry) {
                return $this->memoryToken;
            }

            // 2. Persistent Cache store (if enabled in config)
            if ($this->isCacheEnabled()) {
                $cached = $this->getCacheRepository()->get($this->getCacheKey());
                if (is_string($cached) && !empty($cached)) {
                    $this->memoryToken = $cached;
                    return $cached;
                }
            }
        }

        return $this->requestNewToken();
    }

    /**
     * Clear token from memory and persistent cache.
     */
    public function clearToken(): void
    {
        $this->memoryToken = null;
        $this->memoryTokenExpiry = null;

        if ($this->isCacheEnabled()) {
            $this->getCacheRepository()->forget($this->getCacheKey());
        }
    }

    /**
     * Request a new OAuth token from SnappPay.
     *
     * @throws SnappPayAuthException
     */
    protected function requestNewToken(): string
    {
        $baseUrl = rtrim((string) ($this->config['base_url'] ?? 'https://api.snapppay.ir'), '/');
        $clientId = (string) ($this->config['client_id'] ?? '');
        $clientSecret = (string) ($this->config['client_secret'] ?? '');
        $username = (string) ($this->config['username'] ?? '');
        $password = (string) ($this->config['password'] ?? '');
        $timeout = (int) ($this->config['timeout'] ?? 30);
        $connectTimeout = (int) ($this->config['connect_timeout'] ?? 10);

        if (empty($clientId) || empty($clientSecret) || empty($username) || empty($password)) {
            throw SnappPayAuthException::invalidCredentials('Missing SnappPay API credentials (client_id, client_secret, username, or password).');
        }

        $basicAuth = base64_encode("{$clientId}:{$clientSecret}");

        $response = Http::withHeaders([
            'Authorization' => "Basic {$basicAuth}",
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])
            ->timeout($timeout)
            ->connectTimeout($connectTimeout)
            ->asForm()
            ->post("{$baseUrl}/api/online/v1/oauth/token", [
                'grant_type' => 'password',
                'scope' => 'online-merchant',
                'username' => $username,
                'password' => $password,
            ]);

        if ($response->status() === 403 || str_contains($response->body(), 'Access Denied')) {
            throw SnappPayAuthException::accessDenied($response->body());
        }

        if ($response->status() === 401 || $response->failed()) {
            throw SnappPayAuthException::invalidCredentials($response->body());
        }

        $data = $response->json();
        $token = (string) ($data['access_token'] ?? '');
        $expiresIn = (int) ($data['expires_in'] ?? 3600);

        if (empty($token)) {
            throw SnappPayAuthException::invalidCredentials('OAuth response missing access_token.');
        }

        // Keep in memory with 60s safety buffer
        $ttl = max(60, $expiresIn - 60);
        $this->memoryToken = $token;
        $this->memoryTokenExpiry = time() + $ttl;

        if ($this->isCacheEnabled()) {
            $this->getCacheRepository()->put($this->getCacheKey(), $token, $ttl);
        }

        return $token;
    }

    protected function isCacheEnabled(): bool
    {
        return (bool) ($this->config['cache_token'] ?? false);
    }

    protected function getCacheKey(): string
    {
        $prefix = (string) ($this->config['cache_prefix'] ?? 'snapppay_token_');
        $hash = md5(($this->config['client_id'] ?? '') . ':' . ($this->config['username'] ?? ''));

        return "{$prefix}{$hash}";
    }

    protected function getCacheRepository(): CacheRepository
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $store = $this->config['cache_store'] ?? null;

        return Cache::store($store);
    }
}
