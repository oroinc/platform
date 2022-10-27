<?php

namespace Oro\Bundle\GoogleIntegrationBundle\Tests\Unit\OAuth;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\GoogleIntegrationBundle\OAuth\GoogleOAuthUserProvider;
use Oro\Bundle\GoogleIntegrationBundle\Tests\Unit\Stub\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class GoogleOAuthUserProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userManager;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var GoogleOAuthUserProvider */
    private $userProvider;

    protected function setUp(): void
    {
        $this->userManager = $this->createMock(UserManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->userProvider = new GoogleOAuthUserProvider($this->userManager, $this->configManager);
    }

    /**
     * @dataProvider isEnabledDataProvider
     */
    public function testIsEnabled($configValue, bool $expectedResult): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_google_integration.enable_sso')
            ->willReturn($configValue);

        self::assertSame($expectedResult, $this->userProvider->isEnabled());
    }

    public function isEnabledDataProvider(): array
    {
        return [
            [true, true],
            [false, false],
            [1, true],
            [0, false],
            [null, false]
        ];
    }

    /**
     * @dataProvider getAllowedDomainsDataProvider
     */
    public function testGetAllowedDomains($configValue, array $expectedResult): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_google_integration.sso_domains')
            ->willReturn($configValue);

        self::assertSame($expectedResult, $this->userProvider->getAllowedDomains());
    }

    public function getAllowedDomainsDataProvider(): array
    {
        return [
            [[], []],
            [['domain1'], ['domain1']],
            [['domain1', 'domain2'], ['domain1', 'domain2']],
            [null, []]
        ];
    }

    public function testFindUserWhenUserFoundByGoogleId(): void
    {
        $response = $this->createMock(UserResponseInterface::class);
        $response->expects(self::once())
            ->method('getUsername')
            ->willReturn('username');
        $response->expects(self::never())
            ->method('getEmail');

        $user = new User();

        $this->userManager->expects(self::once())
            ->method('findUserBy')
            ->with(['googleId' => 'username'])
            ->willReturn($user);
        $this->userManager->expects(self::never())
            ->method('findUserByEmail');
        $this->userManager->expects(self::never())
            ->method('updateUser');

        self::assertSame($user, $this->userProvider->findUser($response));
    }

    public function testFindUserWhenUserNotFoundByGoogleIdButFoundByEmail(): void
    {
        $response = $this->createMock(UserResponseInterface::class);
        $response->expects(self::once())
            ->method('getUsername')
            ->willReturn('username');
        $response->expects(self::once())
            ->method('getEmail')
            ->willReturn('user@test.com');

        $user = new User();

        $this->userManager->expects(self::once())
            ->method('findUserBy')
            ->with(['googleId' => 'username'])
            ->willReturn(null);
        $this->userManager->expects(self::once())
            ->method('findUserByEmail')
            ->with('user@test.com')
            ->willReturn($user);
        $this->userManager->expects(self::once())
            ->method('updateUser')
            ->with(self::identicalTo($user))
            ->willReturnCallback(function (User $user) {
                self::assertEquals('username', $user->getGoogleId());
            });

        self::assertSame($user, $this->userProvider->findUser($response));
    }

    public function testFindUserWhenUserNotFoundByGoogleIdAndNoUserEmail(): void
    {
        $response = $this->createMock(UserResponseInterface::class);
        $response->expects(self::once())
            ->method('getUsername')
            ->willReturn('username');
        $response->expects(self::once())
            ->method('getEmail')
            ->willReturn('');

        $this->userManager->expects(self::once())
            ->method('findUserBy')
            ->with(['googleId' => 'username'])
            ->willReturn(null);
        $this->userManager->expects(self::never())
            ->method('findUserByEmail');
        $this->userManager->expects(self::never())
            ->method('updateUser');

        self::assertNull($this->userProvider->findUser($response));
    }

    public function testFindUserWhenUserNotFound(): void
    {
        $response = $this->createMock(UserResponseInterface::class);
        $response->expects(self::once())
            ->method('getUsername')
            ->willReturn('username');
        $response->expects(self::once())
            ->method('getEmail')
            ->willReturn('user@test.com');

        $this->userManager->expects(self::once())
            ->method('findUserBy')
            ->with(['googleId' => 'username'])
            ->willReturn(null);
        $this->userManager->expects(self::once())
            ->method('findUserByEmail')
            ->with('user@test.com')
            ->willReturn(null);
        $this->userManager->expects(self::never())
            ->method('updateUser');

        self::assertNull($this->userProvider->findUser($response));
    }
}
