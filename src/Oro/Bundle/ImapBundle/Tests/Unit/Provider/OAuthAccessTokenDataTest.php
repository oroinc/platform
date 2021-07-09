<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Provider;

use Oro\Bundle\ImapBundle\Provider\OAuthAccessTokenData;
use PHPUnit\Framework\TestCase;

class OAuthAccessTokenDataTest extends TestCase
{
    public function testTokenData(): void
    {
        $tokenData = new OAuthAccessTokenData(
            'test_access_token_1',
            'test_refresh_token_1',
            3600
        );

        $this->assertEquals('test_access_token_1', $tokenData->getAccessToken());
        $this->assertEquals('test_refresh_token_1', $tokenData->getRefreshToken());
        $this->assertEquals(3600, $tokenData->getExpiresIn());
    }

    public function testTokenDataWithoutRefreshToken(): void
    {
        $tokenData = new OAuthAccessTokenData(
            'test_access_token_2',
            null,
            7200
        );

        $this->assertEquals('test_access_token_2', $tokenData->getAccessToken());
        $this->assertNull($tokenData->getRefreshToken());
        $this->assertEquals(7200, $tokenData->getExpiresIn());
    }

    public function testTokenDataWithoutRefreshTokenAndExpiresIn(): void
    {
        $tokenData = new OAuthAccessTokenData(
            'test_access_token_3',
            null,
            null
        );

        $this->assertEquals('test_access_token_3', $tokenData->getAccessToken());
        $this->assertNull($tokenData->getRefreshToken());
        $this->assertNull($tokenData->getExpiresIn());
    }

    public function testTokenDataWithoutAccessToken(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The access token must not be empty.');
        new OAuthAccessTokenData('', null, null);
    }
}
