<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Eamirgh\SnappPay\Exceptions\SnappPayAuthException;
use Eamirgh\SnappPay\Services\TokenManager;
use Eamirgh\SnappPay\Tests\TestCase;

class TokenManagerTest extends TestCase
{
    protected array $config = [
        'base_url' => 'https://api.snapppay.test',
        'client_id' => 'my_client',
        'client_secret' => 'my_secret',
        'username' => 'my_user',
        'password' => 'my_pass',
        'timeout' => 30,
        'connect_timeout' => 10,
        'cache_token' => false,
    ];

    public function test_successful_token_retrieval(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'sample_jwt_token',
                'token_type' => 'bearer',
                'expires_in' => 3600,
                'scope' => 'online-merchant',
            ], 200),
        ]);

        $manager = new TokenManager($this->config);
        $token = $manager->getToken();

        $this->assertSame('sample_jwt_token', $token);

        Http::assertSent(function ($request) {
            $expectedBasic = 'Basic ' . base64_encode('my_client:my_secret');
            return $request->url() === 'https://api.snapppay.test/api/online/v1/oauth/token'
                && $request->hasHeader('Authorization', $expectedBasic)
                && $request['grant_type'] === 'password'
                && $request['scope'] === 'online-merchant'
                && $request['username'] === 'my_user'
                && $request['password'] === 'my_pass';
        });
    }

    public function test_in_memory_token_reuse(): void
    {
        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::sequence()
                ->push(['access_token' => 'token_1', 'expires_in' => 3600])
                ->push(['access_token' => 'token_2', 'expires_in' => 3600]),
        ]);

        $manager = new TokenManager($this->config);

        $t1 = $manager->getToken();
        $t2 = $manager->getToken();

        $this->assertSame('token_1', $t1);
        $this->assertSame('token_1', $t2);
        Http::assertSentCount(1);

        // Force fresh
        $t3 = $manager->getToken(forceFresh: true);
        $this->assertSame('token_2', $t3);
        Http::assertSentCount(2);
    }

    public function test_cache_store_integration_when_enabled(): void
    {
        $cacheConfig = array_merge($this->config, ['cache_token' => true]);

        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'cached_jwt_token',
                'expires_in' => 3600,
            ], 200),
        ]);

        Cache::flush();

        $manager1 = new TokenManager($cacheConfig);
        $t1 = $manager1->getToken();
        $this->assertSame('cached_jwt_token', $t1);
        Http::assertSentCount(1);

        // A second instance should find the token in Cache store
        $manager2 = new TokenManager($cacheConfig);
        $t2 = $manager2->getToken();
        $this->assertSame('cached_jwt_token', $t2);
        Http::assertSentCount(1); // No second HTTP request made
    }

    public function test_unauthorized_credentials_throw_auth_exception(): void
    {
        $this->expectException(SnappPayAuthException::class);

        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response([
                'error' => 'invalid_client',
            ], 401),
        ]);

        $manager = new TokenManager($this->config);
        $manager->getToken();
    }

    public function test_access_denied_ip_whitelist_exception(): void
    {
        $this->expectException(SnappPayAuthException::class);
        $this->expectExceptionMessage('Access Denied: Server IP is not whitelisted');

        Http::fake([
            'https://api.snapppay.test/api/online/v1/oauth/token' => Http::response(
                'Access Denied. Your IP is not whitelisted.',
                403
            ),
        ]);

        $manager = new TokenManager($this->config);
        $manager->getToken();
    }

    public function test_missing_credentials_throws(): void
    {
        $this->expectException(SnappPayAuthException::class);

        $manager = new TokenManager([
            'client_id' => '',
            'client_secret' => '',
            'username' => '',
            'password' => '',
        ]);

        $manager->getToken();
    }
}
