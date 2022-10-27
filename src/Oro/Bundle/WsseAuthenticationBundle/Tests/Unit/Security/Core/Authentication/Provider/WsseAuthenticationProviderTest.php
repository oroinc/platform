<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Tests\Unit\Security\Core\Authentication\Provider;

use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Exception\BadUserOrganizationException;
use Oro\Bundle\SecurityBundle\Model\Role;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;
use Oro\Bundle\WsseAuthenticationBundle\Security\Core\Authentication\Provider\WsseAuthenticationProvider;
use Oro\Bundle\WsseAuthenticationBundle\Security\WsseTokenFactory;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken as Token;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class WsseAuthenticationProviderTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_NONCE = 'тестовый апи'; // RU chars used to enforce nonce to contain / symbol in base64
    private const TEST_API_KEY = 'someApiKey';
    private const PROVIDER_KEY = 'someProviderKey';

    /** @var \PHPUnit\Framework\MockObject\MockObject|UserProviderInterface */
    private $userProvider;

    /** @var MessageDigestPasswordEncoder */
    private $encoder;

    /** @var WsseAuthenticationProvider */
    private $provider;

    /** @var UserCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $userChecker;

    protected function setUp(): void
    {
        $this->userProvider = $this->createMock(UserProviderInterface::class);
        $this->encoder = new MessageDigestPasswordEncoder('sha1', true, 1);
        $cache = new ArrayAdapter();
        $this->userChecker = $this->createMock(UserCheckerInterface::class);

        $this->provider = new WsseAuthenticationProvider(
            $this->userChecker,
            new WsseTokenFactory(),
            $this->userProvider,
            self::PROVIDER_KEY,
            $this->encoder,
            $cache
        );
    }

    public function testAuthenticateOnCorrectData(): void
    {
        $user = $this->getUser();
        $token = $this->prepareTestInstance($user, self::TEST_API_KEY);

        $token = $this->provider->authenticate($token);
        self::assertTrue($token->isAuthenticated());
        self::assertEquals($user, $token->getUser());
    }

    public function getUser(): User
    {
        $organization = new Organization();
        $organization->setEnabled(true);

        $userApiKey = new UserApi();
        $userApiKey->setApiKey(self::TEST_API_KEY);
        $userApiKey->setOrganization($organization);

        $advancedUser = new User();
        $advancedUser->addOrganization($organization);
        $advancedUser->addApiKey($userApiKey);
        $advancedUser->setEnabled(true);
        $advancedUser->setAuthStatus(new TestEnumValue(UserManager::STATUS_ACTIVE, UserManager::STATUS_ACTIVE));
        $role = $this->createMock(Role::class);
        $advancedUser->setUserRoles([$role]);
        $advancedUser->setUsername('sample_user');
        $userApiKey->setUser($advancedUser);

        return $advancedUser;
    }

    /**
     * @dataProvider wrongUserProvider
     */
    public function testAuthenticateOnWrongData(
        User $user,
        string $secret,
        string $exceptionType,
        string $exceptionString,
        bool $isEnabledUser,
        bool $isLockedUserAuthStatus
    ): void {
        if (!$isEnabledUser) {
            $this->userChecker->expects(self::once())
                ->method('checkPreAuth')
                ->with($user)
                ->willThrowException(new DisabledException('User account is disabled.'));
        }

        if ($isLockedUserAuthStatus) {
            $this->userChecker->expects(self::once())
                ->method('checkPreAuth')
                ->with($user)
                ->willThrowException(new LockedException('User account is locked.'));
        }

        $token = $this->prepareTestInstance($user, $secret);
        self::assertFalse($token->isAuthenticated());

        $this->expectException($exceptionType);
        $this->expectExceptionMessage($exceptionString);

        $this->provider->authenticate($token);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function wrongUserProvider(): array
    {
        $organization1 = new Organization();
        $organization1->setName('organization1');
        $organization1->setEnabled(true);

        $organization2 = new Organization();
        $organization1->setName('organization2');
        $organization2->setEnabled(true);

        $disabledOrganization = new Organization();
        $disabledOrganization->setEnabled(false);

        $userApiKey = new UserApi();
        $userApiKey->setApiKey(self::TEST_API_KEY);
        $userApiKey->setOrganization($disabledOrganization);

        $activeAuthStatus = new TestEnumValue(UserManager::STATUS_ACTIVE, UserManager::STATUS_ACTIVE);
        $lockedAuthStatus = new TestEnumValue('locked', 'locked');

        $user = new User();
        $user->addOrganization($disabledOrganization);
        $user->addApiKey($userApiKey);
        $user->setEnabled(true);
        $user->setAuthStatus($activeAuthStatus);
        $user->setUsername('sample_user');
        $userApiKey->setUser($user);

        $org1ApiKey = new UserApi();
        $org1ApiKey->setApiKey(self::TEST_API_KEY);
        $org1ApiKey->setOrganization($organization1);

        $userWithWrongKey = new User();
        $userWithWrongKey->addOrganization($organization2);
        $userWithWrongKey->addApiKey($org1ApiKey);
        $userWithWrongKey->setEnabled(true);
        $userWithWrongKey->setAuthStatus($activeAuthStatus);
        $userWithWrongKey->setUsername('sample_user_wrong_api');

        $org2ApiKey = new UserApi();
        $org2ApiKey->setApiKey(self::TEST_API_KEY);
        $org2ApiKey->setOrganization($organization2);

        $disabledUser = new User();
        $disabledUser->addOrganization($organization2);
        $disabledUser->addApiKey($org2ApiKey);
        $disabledUser->setEnabled(false);
        $disabledUser->setAuthStatus($activeAuthStatus);
        $disabledUser->setUsername('sample_user_disabled');

        $lockedUser = new User();
        $lockedUser->addOrganization($organization2);
        $lockedUser->addApiKey($org2ApiKey);
        $lockedUser->setEnabled(true);
        $lockedUser->setAuthStatus($lockedAuthStatus);
        $lockedUser->setUsername('sample_user_locked');

        return [
            'disabled organization' => [
                $user,
                self::TEST_API_KEY,
                BadUserOrganizationException::class,
                'Organization is not active.',
                $user->isEnabled(),
                $user->getAuthStatus()->getId() === $lockedAuthStatus->getId(),
            ],
            'wrong API key' => [
                $user,
                'wrong key',
                AuthenticationException::class,
                'WSSE authentication failed.',
                $user->isEnabled(),
                $user->getAuthStatus()->getId() === $lockedAuthStatus->getId(),
            ],
            'API key from another organization' => [
                $userWithWrongKey,
                self::TEST_API_KEY,
                BadCredentialsException::class,
                'Wrong API key.',
                $userWithWrongKey->isEnabled(),
                $userWithWrongKey->getAuthStatus()->getId() === $lockedAuthStatus->getId(),
            ],
            'disabled user' => [
                $disabledUser,
                self::TEST_API_KEY,
                DisabledException::class,
                'User account is disabled.',
                $disabledUser->isEnabled(),
                $disabledUser->getAuthStatus()->getId() === $lockedAuthStatus->getId(),
            ],
            'locked user' => [
                $lockedUser,
                self::TEST_API_KEY,
                LockedException::class,
                'User account is locked.',
                $lockedUser->isEnabled(),
                $lockedUser->getAuthStatus()->getId() === $lockedAuthStatus->getId(),
            ],
        ];
    }

    public function testGetSecretException(): void
    {
        $this->expectException(AuthenticationException::class);

        $noApiKeyUser = new User();
        $noApiKeyUser->setUsername('sample_user_no_api');

        $this->userProvider->expects(self::once())
            ->method('loadUserByUsername')
            ->willReturn($noApiKeyUser);

        $nonce = $this->getNonce();
        $time = date('Y-m-d H:i:s');

        $digest = $this->encoder->encodePassword(sprintf('%s%s%s', base64_decode($nonce), $time, ''), '');

        $token = new Token($noApiKeyUser, 'asd', 'wrongKey');
        $token->setAttribute('digest', $digest);
        $token->setAttribute('nonce', $nonce);
        $token->setAttribute('created', $time);

        $this->provider->authenticate($token);
    }

    public function testIsSupportWithWrongTokenType(): void
    {
        $token = new AnonymousToken('test', 'test');
        self::assertFalse($this->provider->supports($token));
    }

    public function testIsSupport(): void
    {
        $token = new Token(new User(), 'asd', self::PROVIDER_KEY);
        $token->setAttribute('firewallName', 'test');
        $token->setAttribute('nonce', $this->getNonce());
        $token->setAttribute('created', date('Y-m-d H:i:s'));
        self::assertTrue($this->provider->supports($token));
    }

    private function prepareTestInstance(User $user, string $secret): Token
    {
        $this->userProvider->expects(self::any())
            ->method('loadUserByUsername')
            ->willReturn($user);

        $nonce = $this->getNonce();
        $time = date('Y-m-d H:i:s');

        $digest = $this->encoder->encodePassword(sprintf('%s%s%s', base64_decode($nonce), $time, $secret), '');

        $token = new Token($user, $digest, self::PROVIDER_KEY, $user->getRoles());
        $token->setAttribute('digest', $digest);
        $token->setAttribute('nonce', $nonce);
        $token->setAttribute('created', $time);
        $token->setAttribute('firewallName', 'test');

        return $token;
    }

    private function getNonce(): string
    {
        return base64_encode(uniqid(self::TEST_NONCE, true));
    }
}
