<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Controller;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Oro\Bundle\ImapBundle\Controller\GmailAccessTokenController;
use Oro\Bundle\ImapBundle\Manager\OAuthTokenStorage;
use Oro\Bundle\ImapBundle\Provider\GoogleOAuthProvider;
use Oro\Bundle\ImapBundle\Provider\OAuthAccessTokenData;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class GmailAccessTokenControllerTest extends TestCase
{
    public function testAccessTokenActionReturnsHandleWithoutOAuthCredentials(): void
    {
        $accessTokenData = new OAuthAccessTokenData('access-token', 'refresh-token', 3600);
        $userInfo = $this->createMock(UserResponseInterface::class);
        $userInfo->expects(self::once())
            ->method('getEmail')
            ->willReturn('user@example.com');

        $oauthProvider = $this->createMock(GoogleOAuthProvider::class);
        $oauthProvider->expects(self::once())
            ->method('getAccessTokenByAuthCode')
            ->with('authorization-code', null)
            ->willReturn($accessTokenData);
        $oauthProvider->expects(self::once())
            ->method('getUserInfo')
            ->with('access-token')
            ->willReturn($userInfo);

        $tokenStorage = $this->createMock(OAuthTokenStorage::class);
        $tokenStorage->expects(self::once())
            ->method('saveAccessTokenAndReturnHandleCode')
            ->with('gmail', 'access-token', 'refresh-token', 3600)
            ->willReturn('oauth-token-handle');

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [GoogleOAuthProvider::class, $oauthProvider],
                [OAuthTokenStorage::class, $tokenStorage]
            ]);

        $controller = new GmailAccessTokenController();
        $controller->setContainer($container);

        $response = $controller->accessTokenAction(
            new Request(request: ['code' => 'authorization-code'])
        );
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            [
                'oauth_token_handle' => 'oauth-token-handle',
                'email_address' => 'user@example.com'
            ],
            $responseData
        );
        self::assertArrayNotHasKey('access_token', $responseData);
        self::assertArrayNotHasKey('refresh_token', $responseData);
    }
}
