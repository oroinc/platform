<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Provider;

use Http\Client\Common\HttpMethodsClientInterface;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Exception\OAuthAccessTokenFailureException;
use Oro\Bundle\ImapBundle\Exception\RefreshOAuthAccessTokenFailureException;
use Oro\Bundle\ImapBundle\Provider\MicrosoftOAuthProvider;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class MicrosoftOAuthProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var HttpMethodsClientInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $httpClient;

    /** @var ResourceOwnerMap|\PHPUnit\Framework\MockObject\MockObject */
    private $resourceOwnerMap;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var MicrosoftOAuthProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpMethodsClientInterface::class);
        $this->resourceOwnerMap = $this->createMock(ResourceOwnerMap::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $crypter = $this->createMock(SymmetricCrypterInterface::class);
        $crypter->expects(self::any())
            ->method('decryptData')
            ->with(self::isType('string'))
            ->willReturnCallback(function ($data) {
                return $data . ' (decrypted)';
            });

        $this->provider = new MicrosoftOAuthProvider(
            $this->httpClient,
            $this->resourceOwnerMap,
            $this->configManager,
            $crypter,
            $this->urlGenerator
        );
    }

    private function expectGetConfig(array $values, bool $assertCallCount = true): void
    {
        $map = [];
        foreach ($values as $key => $val) {
            $map[] = [$key, false, false, null, $val];
        }
        $this->configManager->expects($assertCallCount ? self::exactly(count($map)) : self::any())
            ->method('get')
            ->willReturnMap($map);
    }

    private function expectSendRequest(array $parameters): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::once())
            ->method('getBody')
            ->willReturn(json_encode([
                'access_token'  => 'sampleAccessToken',
                'refresh_token' => 'sampleRefreshToken',
                'expires_in'    => 3600
            ], JSON_THROW_ON_ERROR));

        $this->httpClient->expects(self::once())
            ->method('post')
            ->with(
                'https://login.microsoftonline.com/sampleTenant/oauth2/v2.0/token',
                self::isType('array'),
                self::isType('string')
            )
            ->willReturnCallback(function ($url, $headers, $body) use ($parameters, $response) {
                $bodyValues = [];
                parse_str($body, $bodyValues);
                self::assertEquals($parameters, $bodyValues);
                $content = http_build_query($parameters);
                self::assertEquals($content, $body);
                self::assertEquals(
                    [
                        'Content-length' => strlen($content),
                        'content-type'   => 'application/x-www-form-urlencoded',
                        'user-agent'     => 'oro-oauth'
                    ],
                    $headers
                );

                return $response;
            });
    }

    public function testGetAuthorizationUrl(): void
    {
        $this->expectGetConfig([
            'oro_microsoft_integration.tenant' => 'sampleTenant'
        ]);

        self::assertEquals(
            'https://login.microsoftonline.com/sampleTenant/oauth2/v2.0/authorize',
            $this->provider->getAuthorizationUrl()
        );
    }

    public function testGetRedirectUrl(): void
    {
        $redirectUrl = 'https://return.example.com/';
        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with('oro_imap_microsoft_access_token', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($redirectUrl);

        self::assertEquals($redirectUrl, $this->provider->getRedirectUrl());
    }

    public function testGetAccessTokenByAuthCode(): void
    {
        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with('oro_imap_microsoft_access_token', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://return.example.com/');

        $this->expectGetConfig([
            'oro_microsoft_integration.client_id'     => 'sampleClientId',
            'oro_microsoft_integration.client_secret' => 'sampleClientSecret',
            'oro_microsoft_integration.tenant'        => 'sampleTenant'
        ]);

        $this->expectSendRequest([
            'client_id'     => 'sampleClientId',
            'client_secret' => 'sampleClientSecret (decrypted)',
            'grant_type'    => 'authorization_code',
            'code'          => 'sampleCode',
            'redirect_uri'  => 'https://return.example.com/'
        ]);

        $tokenData = $this->provider->getAccessTokenByAuthCode('sampleCode');
        self::assertEquals('sampleAccessToken', $tokenData->getAccessToken());
        self::assertEquals('sampleRefreshToken', $tokenData->getRefreshToken());
        self::assertEquals(3600, $tokenData->getExpiresIn());
    }

    public function testGetAccessTokenByAuthCodeWithEmptyScopes(): void
    {
        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with('oro_imap_microsoft_access_token', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://return.example.com/');

        $this->expectGetConfig([
            'oro_microsoft_integration.client_id'     => 'sampleClientId',
            'oro_microsoft_integration.client_secret' => 'sampleClientSecret',
            'oro_microsoft_integration.tenant'        => 'sampleTenant'
        ]);

        $this->expectSendRequest([
            'client_id'     => 'sampleClientId',
            'client_secret' => 'sampleClientSecret (decrypted)',
            'grant_type'    => 'authorization_code',
            'code'          => 'sampleCode',
            'redirect_uri'  => 'https://return.example.com/'
        ]);

        $tokenData = $this->provider->getAccessTokenByAuthCode('sampleCode', []);
        self::assertEquals('sampleAccessToken', $tokenData->getAccessToken());
        self::assertEquals('sampleRefreshToken', $tokenData->getRefreshToken());
        self::assertEquals(3600, $tokenData->getExpiresIn());
    }

    public function testGetAccessTokenByAuthCodeWithCustomScopes(): void
    {
        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with('oro_imap_microsoft_access_token', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://return.example.com/');

        $this->expectGetConfig([
            'oro_microsoft_integration.client_id'     => 'sampleClientId',
            'oro_microsoft_integration.client_secret' => 'sampleClientSecret',
            'oro_microsoft_integration.tenant'        => 'sampleTenant'
        ]);

        $this->expectSendRequest([
            'client_id'     => 'sampleClientId',
            'client_secret' => 'sampleClientSecret (decrypted)',
            'grant_type'    => 'authorization_code',
            'code'          => 'sampleCode',
            'redirect_uri'  => 'https://return.example.com/',
            'scope'         => 'scope1 scope2'
        ]);

        $tokenData = $this->provider->getAccessTokenByAuthCode('sampleCode', ['scope1', 'scope2']);
        self::assertEquals('sampleAccessToken', $tokenData->getAccessToken());
        self::assertEquals('sampleRefreshToken', $tokenData->getRefreshToken());
        self::assertEquals(3600, $tokenData->getExpiresIn());
    }

    public function testGetAccessTokenByAuthCodeWhenFirstRequestFailed(): void
    {
        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with('oro_imap_microsoft_access_token', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://return.example.com/');

        $this->expectGetConfig([
            'oro_microsoft_integration.client_id'     => 'sampleClientId',
            'oro_microsoft_integration.client_secret' => 'sampleClientSecret',
            'oro_microsoft_integration.tenant'        => 'sampleTenant'
        ], false);

        $response1 = $this->createMock(ResponseInterface::class);
        $response1->expects(self::once())
            ->method('getBody')
            ->willReturn('');

        $response2 = $this->createMock(ResponseInterface::class);
        $response2->expects(self::once())
            ->method('getBody')
            ->willReturn(json_encode([
                'access_token'  => 'sampleAccessToken',
                'refresh_token' => 'sampleRefreshToken',
                'expires_in'    => 3600
            ], JSON_THROW_ON_ERROR));

        $this->httpClient->expects(self::exactly(2))
            ->method('post')
            ->with(
                'https://login.microsoftonline.com/sampleTenant/oauth2/v2.0/token',
                self::isType('array'),
                self::isType('string')
            )
            ->willReturnOnConsecutiveCalls($response1, $response2);

        $tokenData = $this->provider->getAccessTokenByAuthCode('sampleCode');
        self::assertEquals('sampleAccessToken', $tokenData->getAccessToken());
        self::assertEquals('sampleRefreshToken', $tokenData->getRefreshToken());
        self::assertEquals(3600, $tokenData->getExpiresIn());
    }

    public function testGetAccessTokenByAuthCodeWhenAllRequestsFailed(): void
    {
        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with('oro_imap_microsoft_access_token', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://return.example.com/');

        $this->expectGetConfig([
            'oro_microsoft_integration.client_id'     => 'sampleClientId',
            'oro_microsoft_integration.client_secret' => 'sampleClientSecret',
            'oro_microsoft_integration.tenant'        => 'sampleTenant'
        ], false);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::any())
            ->method('getBody')
            ->willReturn('');

        $this->httpClient->expects(self::exactly(4))
            ->method('post')
            ->with(
                'https://login.microsoftonline.com/sampleTenant/oauth2/v2.0/token',
                self::isType('array'),
                self::isType('string')
            )
            ->willReturn($response);

        $this->expectException(OAuthAccessTokenFailureException::class);
        $this->expectExceptionMessage('Cannot get OAuth access token. Authorization Code: sampleCode.');

        $this->provider->getAccessTokenByAuthCode('sampleCode');
    }

    public function testGetAccessTokenByRefreshToken(): void
    {
        $this->urlGenerator->expects(self::never())
            ->method('generate');

        $this->expectGetConfig([
            'oro_microsoft_integration.client_id'     => 'sampleClientId',
            'oro_microsoft_integration.client_secret' => 'sampleClientSecret',
            'oro_microsoft_integration.tenant'        => 'sampleTenant'
        ]);

        $this->expectSendRequest([
            'client_id'     => 'sampleClientId',
            'client_secret' => 'sampleClientSecret (decrypted)',
            'grant_type'    => 'refresh_token',
            'refresh_token' => 'sampleSourceRefreshToken'
        ]);

        $tokenData = $this->provider->getAccessTokenByRefreshToken('sampleSourceRefreshToken');
        self::assertEquals('sampleAccessToken', $tokenData->getAccessToken());
        self::assertEquals('sampleRefreshToken', $tokenData->getRefreshToken());
        self::assertEquals(3600, $tokenData->getExpiresIn());
    }

    public function testGetAccessTokenByRefreshTokenWithEmptyScopes(): void
    {
        $this->urlGenerator->expects(self::never())
            ->method('generate');

        $this->expectGetConfig([
            'oro_microsoft_integration.client_id'     => 'sampleClientId',
            'oro_microsoft_integration.client_secret' => 'sampleClientSecret',
            'oro_microsoft_integration.tenant'        => 'sampleTenant'
        ]);

        $this->expectSendRequest([
            'client_id'     => 'sampleClientId',
            'client_secret' => 'sampleClientSecret (decrypted)',
            'grant_type'    => 'refresh_token',
            'refresh_token' => 'sampleSourceRefreshToken'
        ]);

        $tokenData = $this->provider->getAccessTokenByRefreshToken('sampleSourceRefreshToken', []);
        self::assertEquals('sampleAccessToken', $tokenData->getAccessToken());
        self::assertEquals('sampleRefreshToken', $tokenData->getRefreshToken());
        self::assertEquals(3600, $tokenData->getExpiresIn());
    }

    public function testGetAccessTokenByRefreshTokenWithCustomScopes(): void
    {
        $this->urlGenerator->expects(self::never())
            ->method('generate');

        $this->expectGetConfig([
            'oro_microsoft_integration.client_id'     => 'sampleClientId',
            'oro_microsoft_integration.client_secret' => 'sampleClientSecret',
            'oro_microsoft_integration.tenant'        => 'sampleTenant'
        ]);

        $this->expectSendRequest([
            'client_id'     => 'sampleClientId',
            'client_secret' => 'sampleClientSecret (decrypted)',
            'grant_type'    => 'refresh_token',
            'refresh_token' => 'sampleSourceRefreshToken',
            'scope'         => 'scope1 scope2'
        ]);

        $tokenData = $this->provider->getAccessTokenByRefreshToken('sampleSourceRefreshToken', ['scope1', 'scope2']);
        self::assertEquals('sampleAccessToken', $tokenData->getAccessToken());
        self::assertEquals('sampleRefreshToken', $tokenData->getRefreshToken());
        self::assertEquals(3600, $tokenData->getExpiresIn());
    }

    public function testGetAccessTokenByRefreshTokenWhenFirstRequestFailed(): void
    {
        $this->expectGetConfig([
            'oro_microsoft_integration.client_id'     => 'sampleClientId',
            'oro_microsoft_integration.client_secret' => 'sampleClientSecret',
            'oro_microsoft_integration.tenant'        => 'sampleTenant'
        ], false);

        $response1 = $this->createMock(ResponseInterface::class);
        $response1->expects(self::once())
            ->method('getBody')
            ->willReturn('');

        $response2 = $this->createMock(ResponseInterface::class);
        $response2->expects(self::once())
            ->method('getBody')
            ->willReturn(json_encode([
                'access_token'  => 'sampleAccessToken',
                'refresh_token' => 'sampleRefreshToken',
                'expires_in'    => 3600
            ], JSON_THROW_ON_ERROR));

        $this->httpClient->expects(self::exactly(2))
            ->method('post')
            ->with(
                'https://login.microsoftonline.com/sampleTenant/oauth2/v2.0/token',
                self::isType('array'),
                self::isType('string')
            )
            ->willReturnOnConsecutiveCalls($response1, $response2);

        $tokenData = $this->provider->getAccessTokenByRefreshToken('sampleRefreshToken');
        self::assertEquals('sampleAccessToken', $tokenData->getAccessToken());
        self::assertEquals('sampleRefreshToken', $tokenData->getRefreshToken());
        self::assertEquals(3600, $tokenData->getExpiresIn());
    }

    public function testGetAccessTokenByRefreshTokenWhenAllRequestsFailed(): void
    {
        $this->expectGetConfig([
            'oro_microsoft_integration.client_id'     => 'sampleClientId',
            'oro_microsoft_integration.client_secret' => 'sampleClientSecret',
            'oro_microsoft_integration.tenant'        => 'sampleTenant'
        ], false);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::any())
            ->method('getBody')
            ->willReturn('');

        $this->httpClient->expects(self::exactly(4))
            ->method('post')
            ->with(
                'https://login.microsoftonline.com/sampleTenant/oauth2/v2.0/token',
                self::isType('array'),
                self::isType('string')
            )
            ->willReturn($response);

        $this->expectException(RefreshOAuthAccessTokenFailureException::class);
        $this->expectExceptionMessage('Cannot refresh OAuth access token. Refresh Token: sampleRefreshToken.');

        $this->provider->getAccessTokenByRefreshToken('sampleRefreshToken');
    }

    public function testGetUserInfo(): void
    {
        $accessToken = 'sampleAccessToken';

        $resourceOwner = $this->createMock(ResourceOwnerInterface::class);
        $this->resourceOwnerMap->expects(self::once())
            ->method('getResourceOwnerByName')
            ->with('office365')
            ->willReturn($resourceOwner);

        $response = $this->createMock(UserResponseInterface::class);
        $resourceOwner->expects(self::once())
            ->method('getUserInformation')
            ->with(['access_token' => $accessToken])
            ->willReturn($response);

        self::assertSame($response, $this->provider->getUserInfo($accessToken));
    }
}
