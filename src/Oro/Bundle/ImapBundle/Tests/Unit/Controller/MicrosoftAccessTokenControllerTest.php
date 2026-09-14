<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Controller;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Oro\Bundle\ImapBundle\Controller\MicrosoftAccessTokenController;
use Oro\Bundle\ImapBundle\Manager\OAuthTokenStorage;
use Oro\Bundle\ImapBundle\Provider\MicrosoftOAuthProvider;
use Oro\Bundle\ImapBundle\Provider\OAuthAccessTokenData;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class MicrosoftAccessTokenControllerTest extends TestCase
{
    public function testAccessTokenActionReturnsHandleWithoutOAuthCredentials(): void
    {
        $accessTokenData = new OAuthAccessTokenData('access-token', 'refresh-token', 3600);
        $profileAccessTokenData = new OAuthAccessTokenData('profile-access-token', null, 3600);
        $userInfo = $this->createMock(UserResponseInterface::class);
        $userInfo->expects(self::exactly(2))
            ->method('getEmail')
            ->willReturn('user@example.com');

        $oauthProvider = $this->createMock(MicrosoftOAuthProvider::class);
        $oauthProvider->expects(self::once())
            ->method('getAccessTokenByAuthCode')
            ->with('authorization-code', null)
            ->willReturn($accessTokenData);
        $oauthProvider->expects(self::once())
            ->method('getAccessTokenByRefreshToken')
            ->with('refresh-token', ['openid', 'offline_access', 'profile', 'User.Read'])
            ->willReturn($profileAccessTokenData);
        $oauthProvider->expects(self::exactly(2))
            ->method('getUserInfo')
            ->withConsecutive(['access-token'], ['profile-access-token'])
            ->willReturn($userInfo);

        $tokenStorage = $this->createMock(OAuthTokenStorage::class);
        $tokenStorage->expects(self::once())
            ->method('saveAccessTokenAndReturnHandleCode')
            ->with('microsoft', 'access-token', 'refresh-token', 3600)
            ->willReturn('oauth-token-handle');

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                [MicrosoftOAuthProvider::class, $oauthProvider],
                [OAuthTokenStorage::class, $tokenStorage]
            ]);

        $controller = new MicrosoftAccessTokenController();
        $controller->setContainer($container);

        $session = new Session(new MockArraySessionStorage());
        $callbackRequest = new Request(request: ['code' => 'authorization-code']);
        $callbackRequest->setSession($session);
        $callbackResponse = $controller->accessTokenAction($callbackRequest);

        self::assertEquals('', $callbackResponse->getContent());

        $xhrRequest = new Request(server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $xhrRequest->setSession($session);
        $response = $controller->accessTokenAction($xhrRequest);
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
