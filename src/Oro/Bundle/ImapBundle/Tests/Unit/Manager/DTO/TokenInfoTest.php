<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager\DTO;

use Oro\Bundle\ImapBundle\Manager\DTO\TokenInfo;
use PHPUnit\Framework\TestCase;

class TokenInfoTest extends TestCase
{
    public function testTokenInfo(): void
    {
        $tokenInfo1 = new TokenInfo([
            'access_token' => 'test_access_token_1',
            'refresh_token' => 'test_refresh_token_1',
            'expires_in' => 3600
        ]);

        $this->assertEquals('test_access_token_1', $tokenInfo1->getAccessToken());
        $this->assertEquals('test_refresh_token_1', $tokenInfo1->getRefreshToken());
        $this->assertEquals(3600, $tokenInfo1->getExpiresIn());
        $this->assertEquals([
            'access_token' => 'test_access_token_1',
            'refresh_token' => 'test_refresh_token_1',
            'expires_in' => 3600
        ], $tokenInfo1->toArray());

        $tokenInfo2 = new TokenInfo([
            'access_token' => 'test_access_token_2',
            'expires_in' => 7200
        ]);

        $this->assertEquals('test_access_token_2', $tokenInfo2->getAccessToken());
        $this->assertNull($tokenInfo2->getRefreshToken());
        $this->assertEquals(7200, $tokenInfo2->getExpiresIn());
        $this->assertEquals([
            'access_token' => 'test_access_token_2',
            'refresh_token' => null,
            'expires_in' => 7200
        ], $tokenInfo2->toArray());

        $tokenInfo3 = new TokenInfo([
            'access_token' => 'test_access_token_3',
        ]);

        $this->assertEquals('test_access_token_3', $tokenInfo3->getAccessToken());
        $this->assertNull($tokenInfo3->getRefreshToken());
        $this->assertNull($tokenInfo3->getExpiresIn());
        $this->assertEquals([
            'access_token' => 'test_access_token_3',
            'refresh_token' => null,
            'expires_in' => null
        ], $tokenInfo3->toArray());
    }
}
