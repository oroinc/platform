<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Http\Client\Common\HttpMethodsClientInterface;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationMicrosoftType;
use Oro\Bundle\ImapBundle\Manager\DTO\TokenInfo;
use Oro\Bundle\ImapBundle\Manager\ImapEmailMicrosoftOauth2Manager;
use Oro\Bundle\ImapBundle\Manager\OAuth2ManagerRegistry;
use Oro\Bundle\ImapBundle\Tests\Unit\Stub\TestUserEmailOrigin;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\RouterInterface;

class ImapEmailMicrosoftOauth2ManagerTest extends TestCase
{
    /** @var ImapEmailMicrosoftOauth2Manager */
    protected $manager;

    /** @var HttpMethodsClientInterface|MockObject */
    protected $httpClient;

    /** @var ResourceOwnerMap|MockObject */
    protected $resourceOwnerMap;

    /** @var ConfigManager */
    protected $configManager;

    /** @var ManagerRegistry|MockObject */
    protected $doctrine;

    /** @var RouterInterface|MockObject */
    protected $router;

    /** @var SymmetricCrypterInterface|MockObject */
    protected $crypter;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->httpClient = $this->createHttpClientMock();
        $this->resourceOwnerMap = $this->createResourceOwnerMap();
        $this->configManager = $this->createConfigManager();
        $this->doctrine = $this->createDoctrineMock();
        $this->router = $this->createRouterMock();
        $this->crypter = $this->createCrypterMock();

        $this->manager = new ImapEmailMicrosoftOauth2Manager(
            $this->httpClient,
            $this->resourceOwnerMap,
            $this->configManager,
            $this->doctrine,
            $this->crypter,
            $this->router
        );
    }

    /**
     * Returns mock object for crypter
     *
     * @return MockObject|SymmetricCrypterInterface
     */
    protected function createCrypterMock(): MockObject
    {
        $mock = $this->getMockBuilder(SymmetricCrypterInterface::class)->getMock();
        $mock->expects($this->any())
            ->method('decryptData')
            ->with('sampleClientSecretEncrypted')
            ->willReturn('sampleClientSecret');

        return $mock;
    }

    /**
     * Returns mock object for router
     *
     * @return MockObject|RouterInterface
     */
    protected function createRouterMock(): MockObject
    {
        return $this->getMockBuilder(RouterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return MockObject|OAuth2ManagerRegistry
     */
    protected function createDoctrineMock(): MockObject
    {
        return $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return MockObject|ConfigManager
     */
    protected function createConfigManager(): MockObject
    {
        return $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return MockObject|ResourceOwnerMap
     */
    protected function createResourceOwnerMap(): MockObject
    {
        return $this->getMockBuilder(ResourceOwnerMap::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return MockObject|HttpMethodsClientInterface
     */
    protected function createHttpClientMock(): MockObject
    {
        return $this->getMockBuilder(HttpMethodsClientInterface::class)
            ->getMock();
    }

    public function testGetType(): void
    {
        $this->assertEquals('microsoft', $this->manager->getType());
    }

    public function testGetConnectionFormTypeClass(): void
    {
        $this->assertEquals(ConfigurationMicrosoftType::class, $this->manager->getConnectionFormTypeClass());
    }

    public function testSetOriginDefaults(): void
    {
        /** @var MockObject|UserEmailOrigin $userEmailOrigin */
        $userEmailOrigin = $this->getMockBuilder(UserEmailOrigin::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userEmailOrigin->expects($this->once())
            ->method('setImapHost')
            ->with('outlook.office365.com');
        $userEmailOrigin->expects($this->once())
            ->method('setImapPort')
            ->with(993);

        $this->manager->setOriginDefaults($userEmailOrigin);
    }

    public function testGetResourceOwnerName(): void
    {
        $reflection = new \ReflectionMethod(get_class($this->manager), 'getResourceOwnerName');
        $reflection->setAccessible(true);
        $this->assertEquals('office365', $reflection->invoke($this->manager));
    }

    public function testBuildParameters(): void
    {
        $reflection = new \ReflectionMethod(get_class($this->manager), 'buildParameters');
        $reflection->setAccessible(true);

        $this->router->expects($this->once())
            ->method('generate')
            ->with('oro_imap_microsoft_access_token', [], 0)
            ->willReturn('https://return.example.com/');

        $parameters = $reflection->invoke($this->manager, 'sample_code');

        $this->assertSame([
            'redirect_uri' => 'https://return.example.com/',
            'scope' => 'offline_access https://outlook.office.com/IMAP.AccessAsUser.All https://outlook.office.com/POP.AccessAsUser.All https://outlook.office.com/SMTP.Send', // @codingStandardsIgnoreLine
            'code' => 'sample_code',
            'grant_type' => 'authorization_code'
        ], $parameters);
    }

    public function testGetConfigParameters(): void
    {
        $reflection = new \ReflectionMethod(get_class($this->manager), 'getConfigParameters');
        $reflection->setAccessible(true);

        $this
            ->configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    'oro_microsoft_integration.client_id',
                    false,
                    false,
                    null,
                    'sampleClientId'
                ],
                [
                    'oro_microsoft_integration.client_secret',
                    false,
                    false,
                    null,
                    'sampleClientSecretEncrypted'
                ]
            ]);

        $configParameters = $reflection->invoke($this->manager);

        $this->assertSame([
            'client_id' => 'sampleClientId',
            'client_secret' => 'sampleClientSecret'
        ], $configParameters);
    }

    public function testIsOauthEnabled(): void
    {
        $this
            ->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_imap.enable_microsoft_imap')
            ->willReturn(1);

        $this->assertTrue($this->manager->isOAuthEnabled());
    }

    /**
     * @return MockObject|UserEmailOrigin
     */
    private function getExpirationOrigin(bool $isExpired = false): MockObject
    {
        /** @var MockObject|UserEmailOrigin $userEmailOrigin */
        $userEmailOrigin = $this->getMockBuilder(UserEmailOrigin::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userEmailOrigin->expects($this->once())
            ->method('getAccessTokenExpiresAt')
            ->willReturnCallback(function () use ($isExpired) {
                return $this->getDateObject($isExpired);
            });

        return $userEmailOrigin;
    }

    /**
     * @param bool $isExpired
     * @return \DateTime
     */
    protected function getDateObject(bool $isExpired = false): \DateTime
    {
        $date = new \DateTime();
        $multiplier = $isExpired ? -1 : 1;
        $timestamp = time() + (1000 * $multiplier);
        $date->setTimestamp($timestamp);

        return $date;
    }

    public function testIsAccessTokenExpired(): void
    {
        $this->assertTrue($this->manager->isAccessTokenExpired($this->getExpirationOrigin(true)));
        $this->assertFalse($this->manager->isAccessTokenExpired($this->getExpirationOrigin(false)));
    }

    /**
     * @param UserEmailOrigin $origin
     * @param bool $isConfigEnabled
     * @param string|null $currentToken
     *
     * @dataProvider getExpirationCheckData
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetAccessTokenWithCheckingExpiration(
        UserEmailOrigin $origin,
        bool $isConfigEnabled,
        ?string $currentToken
    ): void {
        $this->assertSame($this->manager, $this->manager->setAccessTokenUrl('https://example.com/{tenant}/'));
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $isExpired = $now > $origin->getAccessTokenExpiresAt();

        $this->router->expects($this->any())
            ->method('generate')
            ->with('oro_imap_microsoft_access_token', [], 0)
            ->willReturn('https://return.example.com/');

        $configCount = !$isExpired ? 0 : ($isConfigEnabled ? 4 : 1);
        $this
            ->configManager
            ->expects($this->exactly($configCount))
            ->method('get')
            ->willReturnMap([
                [
                    'oro_imap.enable_microsoft_imap',
                    false,
                    false,
                    null,
                    (int)$isConfigEnabled
                ],
                [
                    'oro_microsoft_integration.client_id',
                    false,
                    false,
                    null,
                    'sampleClientId'
                ],
                [
                    'oro_microsoft_integration.client_secret',
                    false,
                    false,
                    null,
                    'sampleClientSecretEncrypted'
                ],
                [
                    'oro_microsoft_integration.tenant',
                    false,
                    false,
                    null,
                    'sampleTenant'
                ]
            ]);


        if ($isConfigEnabled && $isExpired) {
            $expectedPrams = [
                'client_id' => 'sampleClientId',
                'client_secret' => 'sampleClientSecret',
                'refresh_token' => 'sampleRefreshToken',
                'grant_type' => 'refresh_token',
                'scope' => 'offline_access https://outlook.office.com/IMAP.AccessAsUser.All https://outlook.office.com/POP.AccessAsUser.All https://outlook.office.com/SMTP.Send', // @codingStandardsIgnoreLine
            ];
            $content = http_build_query($expectedPrams, '', '&');
            $length = strlen($content);

            $this->httpClient->expects($this->once())
                ->method('post')
                ->with('https://example.com/sampleTenant/', [
                    'Content-length' => $length,
                    'content-type' => 'application/x-www-form-urlencoded',
                    'user-agent' => 'oro-oauth'
                ], $content)
                ->willReturn($this->createResponseMock());

            $emMock = $this->getMockBuilder(ObjectManager::class)
                ->getMock();
            $emMock->expects($this->once())
                ->method('persist')
                ->with($origin);
            $emMock->expects($this->once())
                ->method('flush')
                ->with($origin);

            $this
                ->doctrine
                ->expects($this->once())
                ->method('getManagerForClass')
                ->willReturn($emMock);

            $resultToken = $this->manager->getAccessTokenWithCheckingExpiration($origin);
            $this->assertEquals('sampleAccessToken', $resultToken);
        } else {
            $this->httpClient->expects($this->never())
                ->method('post');
            $this
                ->doctrine
                ->expects($this->never())
                ->method('getManagerForClass');

            $resultToken = $this->manager->getAccessTokenWithCheckingExpiration($origin);
            $this->assertEquals($currentToken, $resultToken);
        }
    }

    public function getExpirationCheckData(): array
    {
        $expiredOriginNoToken1 = new TestUserEmailOrigin();
        $expiredOriginNoToken1->setAccessTokenExpiresAt($this->getDateObject(true));
        $expiredOriginNoToken1->setRefreshToken('sampleRefreshToken');


        $expiredOriginNoToken2 = new TestUserEmailOrigin();
        $expiredOriginNoToken2->setAccessTokenExpiresAt($this->getDateObject(true));

        $nonexpiredOriginToken = new TestUserEmailOrigin();
        $nonexpiredOriginToken->setAccessTokenExpiresAt($this->getDateObject());
        $nonexpiredOriginToken->setAccessToken('sampleTokenResult');

        return [
            'expiredOriginNoTokenEnabled' => [
                $expiredOriginNoToken1,
                true,
                null
            ],
            'expiredOriginNoTokenDisabled' => [
                $expiredOriginNoToken2,
                false,
                null
            ],
            'nonexpiredOriginTokenEnabled' => [
                $nonexpiredOriginToken,
                true,
                'sampleTokenResult'
            ]
        ];
    }

    public function testGetAccessTokenByAuthCode(): void
    {
        $this->assertSame(
            $this->manager,
            $this->manager->setAccessTokenUrl('https://example.com/{tenant}/')
        );

        $this->router->expects($this->once())
            ->method('generate')
            ->with('oro_imap_microsoft_access_token', [], 0)
            ->willReturn('https://return.example.com/');

        $this
            ->configManager
            ->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                [
                    'oro_microsoft_integration.client_id',
                    false,
                    false,
                    null,
                    'sampleClientId'
                ],
                [
                    'oro_microsoft_integration.client_secret',
                    false,
                    false,
                    null,
                    'sampleClientSecretEncrypted'
                ],
                [
                    'oro_microsoft_integration.tenant',
                    false,
                    false,
                    null,
                    'sampleTenant'
                ]
            ]);

        $expectedPrams = [
            'client_id' => 'sampleClientId',
            'client_secret' => 'sampleClientSecret',
            'redirect_uri' => 'https://return.example.com/',
            'scope' => 'offline_access https://outlook.office.com/IMAP.AccessAsUser.All https://outlook.office.com/POP.AccessAsUser.All https://outlook.office.com/SMTP.Send', // @codingStandardsIgnoreLine
            'code' => 'sampleCode',
            'grant_type' => 'authorization_code'
        ];
        $content = http_build_query($expectedPrams, '', '&');
        $length = strlen($content);

        $this->httpClient->expects($this->once())
            ->method('post')
            ->with('https://example.com/sampleTenant/', [
                'Content-length' => $length,
                'content-type' => 'application/x-www-form-urlencoded',
                'user-agent' => 'oro-oauth'
            ], $content)
            ->willReturn($this->createResponseMock());

        $tokenData = $this->manager->getAccessTokenDataByAuthCode('sampleCode');
        $this->assertInstanceOf(TokenInfo::class, $tokenData);
        $this->assertEquals('sampleAccessToken', $tokenData->getAccessToken());
        $this->assertEquals('sampleRefreshToken', $tokenData->getRefreshToken());
        $this->assertEquals(3600, $tokenData->getExpiresIn());
    }

    /**
     * @return MockObject|ResponseInterface
     */
    protected function createResponseMock(): MockObject
    {
        $mock = $this->getMockBuilder(ResponseInterface::class)
            ->getMock();
        $mock->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                'access_token' => 'sampleAccessToken',
                'refresh_token' => 'sampleRefreshToken',
                'expires_in' => 3600
            ]));

        return $mock;
    }
}
