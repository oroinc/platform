<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Security\WsseAuthProvider;
use Oro\Bundle\UserBundle\Security\WsseTokenFactory;
use Oro\Bundle\UserBundle\Tests\Unit\Fixture\RegularUser;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken as Token;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class WsseAuthProviderTest extends \PHPUnit\Framework\TestCase
{
    const TEST_SALT = 'someSalt';
    const TEST_PASSWORD = 'somePassword';
    const TEST_NONCE = 'someNonce';
    const TEST_API_KEY = 'someApiKey';
    const PROVIDER_KEY = 'someProviderKey';

    /** @var \PHPUnit\Framework\MockObject\MockObject|UserProviderInterface */
    protected $userProvider;

    /** @var MessageDigestPasswordEncoder */
    protected $encoder;

    /** @var WsseAuthProvider */
    protected $provider;

    /** @var UserCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $userChecker;

    /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $token;

    protected function setUp()
    {
        $this->userProvider = $this->createMock('Symfony\Component\Security\Core\User\UserProviderInterface');
        $this->encoder = new MessageDigestPasswordEncoder('sha1', true, 1);
        $cache = new ArrayCache();
        $this->userChecker = $this->createMock(UserCheckerInterface::class);

        $this->provider = new WsseAuthProvider(
            $this->userChecker,
            $this->userProvider,
            self::PROVIDER_KEY,
            $this->encoder,
            $cache
        );

        $this->token = $this->createMock(TokenInterface::class);
        $this->provider->setTokenFactory(new WsseTokenFactory());
    }

    protected function tearDown()
    {
        unset($this->userProvider, $this->encoder, $this->provider);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     * @expectedExceptionMessage Token Factory is not set in WsseAuthProvider.
     */
    public function testAuthenticateIfTokenFactoryIsNotSet()
    {
        $provider = new WsseAuthProvider(
            $this->userChecker,
            $this->userProvider,
            self::PROVIDER_KEY,
            $this->encoder,
            new ArrayCache()
        );
        $provider->authenticate($this->token);
    }

    /**
     * @dataProvider userProvider
     *
     * @param User $user
     * @param string $secret
     * @param string $salt
     */
    public function testAuthenticateOnCorrectData($user, $secret, $salt = '')
    {
        $token = $this->prepareTestInstance($user, $secret, $salt);

        $token = $this->provider->authenticate($token);
        $this->assertTrue($token->isAuthenticated());
        $this->assertEquals($user, $token->getUser());
    }

    /**
     * @return array
     */
    public function userProvider()
    {
        $regularUser = new RegularUser();
        $regularUser->setPassword(self::TEST_PASSWORD);
        $regularUser->setSalt(self::TEST_SALT);
        $regularUser->setRoles(['admin']);

        $organization = new Organization();
        $organization->setEnabled(true);

        $userApiKey = new UserApi();
        $userApiKey->setApiKey(self::TEST_API_KEY);
        $userApiKey->setOrganization($organization);

        $advancedUser = new User();
        $advancedUser->addOrganization($organization);
        $advancedUser->addApiKey($userApiKey);
        $advancedUser->setEnabled(true);
        $advancedUser->setAuthStatus(new StubEnumValue(UserManager::STATUS_ACTIVE, UserManager::STATUS_ACTIVE));
        $role = $this->createMock(RoleInterface::class);
        $advancedUser->setRoles([$role]);
        $userApiKey->setUser($advancedUser);

        return [
            'regular user given, should use password and salt' => [$regularUser, self::TEST_PASSWORD, self::TEST_SALT],
            'advanced user given, should take API key only' => [$advancedUser, self::TEST_API_KEY]
        ];
    }

    /**
     * @dataProvider wrongUserProvider
     *
     * @param object $user
     * @param string $secret
     * @param string $exceptionType
     * @param string $exceptionString
     */
    public function testAuthenticateOnWrongData(
        $user,
        $secret,
        $exceptionType,
        $exceptionString,
        $isEnabledUser,
        $isLockedUserAuthStatus
    ) {
        if (!$isEnabledUser) {
            $this->userChecker->expects($this->once())
                ->method('checkPreAuth')
                ->with($user)
                ->will($this->throwException(new DisabledException('User account is disabled.')));
        }

        if ($isLockedUserAuthStatus) {
            $this->userChecker->expects($this->once())
                ->method('checkPreAuth')
                ->with($user)
                ->will($this->throwException(new LockedException('User account is locked.')));
        }

        $token = $this->prepareTestInstance($user, $secret);
        $this->assertFalse($token->isAuthenticated());

        $this->expectException($exceptionType);
        $this->expectExceptionMessage($exceptionString);

        $this->provider->authenticate($token);
    }

    /**
     * @return array
     */
    public function wrongUserProvider()
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

        $activeAuthStatus = new StubEnumValue(UserManager::STATUS_ACTIVE, UserManager::STATUS_ACTIVE);
        $lockedAuthStatus = new StubEnumValue('locked', 'locked');

        $user = new User();
        $user->addOrganization($disabledOrganization);
        $user->addApiKey($userApiKey);
        $user->setEnabled(true);
        $user->setAuthStatus($activeAuthStatus);
        $userApiKey->setUser($user);

        $org1ApiKey = new UserApi();
        $org1ApiKey->setApiKey(self::TEST_API_KEY);
        $org1ApiKey->setOrganization($organization1);

        $userWithWrongKey = new User();
        $userWithWrongKey->addOrganization($organization2);
        $userWithWrongKey->addApiKey($org1ApiKey);
        $userWithWrongKey->setEnabled(true);
        $userWithWrongKey->setAuthStatus($activeAuthStatus);

        $org2ApiKey = new UserApi();
        $org2ApiKey->setApiKey(self::TEST_API_KEY);
        $org2ApiKey->setOrganization($organization2);

        $disabledUser = new User();
        $disabledUser->addOrganization($organization2);
        $disabledUser->addApiKey($org2ApiKey);
        $disabledUser->setEnabled(false);
        $disabledUser->setAuthStatus($activeAuthStatus);

        $lockedUser = new User();
        $lockedUser->addOrganization($organization2);
        $lockedUser->addApiKey($org2ApiKey);
        $lockedUser->setEnabled(true);
        $lockedUser->setAuthStatus($lockedAuthStatus);

        return [
            'disabled organization' => [
                $user,
                self::TEST_API_KEY,
                BadCredentialsException::class,
                'Organization is not active.',
                $user->isEnabled(),
                $user->getAuthStatus()->getId() === $lockedAuthStatus->getId()
            ],
            'wrong API key' => [
                $user,
                'wrong key',
                AuthenticationException::class,
                'WSSE authentication failed.',
                $user->isEnabled(),
                $user->getAuthStatus()->getId() === $lockedAuthStatus->getId()
            ],
            'API key from another organization' => [
                $userWithWrongKey,
                self::TEST_API_KEY,
                BadCredentialsException::class,
                'Wrong API key.',
                $userWithWrongKey->isEnabled(),
                $userWithWrongKey->getAuthStatus()->getId() === $lockedAuthStatus->getId()
            ],
            'disabled user' => [
                $disabledUser,
                self::TEST_API_KEY,
                DisabledException::class,
                'User account is disabled.',
                $disabledUser->isEnabled(),
                $disabledUser->getAuthStatus()->getId() === $lockedAuthStatus->getId()
            ],
            'locked user' => [
                $lockedUser,
                self::TEST_API_KEY,
                LockedException::class,
                'User account is locked.',
                $lockedUser->isEnabled(),
                $lockedUser->getAuthStatus()->getId() === $lockedAuthStatus->getId()
            ]
        ];
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function testGetSecretException()
    {
        $noApiKeyUser = $this->createMock('Oro\Bundle\UserBundle\Entity\User');
        $noApiKeyUser
            ->expects(self::once())
            ->method('getApiKeys')
            ->will(static::returnValue(new ArrayCollection()));

        $noApiKeyUser->expects(static::never())->method('getPassword');
        $noApiKeyUser->expects(static::never())->method('getSalt');
        $noApiKeyUser->expects(static::never())->method('getRoles');

        $this->userProvider
            ->expects(self::once())
            ->method('loadUserByUsername')
            ->will(static::returnValue($noApiKeyUser));

        $nonce = base64_encode(uniqid(self::TEST_NONCE));
        $time = date('Y-m-d H:i:s');

        $digest = $this->encoder->encodePassword(
            sprintf('%s%s%s', base64_decode($nonce), $time, ''),
            ''
        );

        $token = new Token(new User(), 'asd', 'wrongKey');
        $token->setAttribute('digest', $digest);
        $token->setAttribute('nonce', $nonce);
        $token->setAttribute('created', $time);

        $this->provider->authenticate($token);
    }

    public function testIsSupportWithWrongTokenType()
    {
        $token = new AnonymousToken('test', 'test');
        $this->assertFalse($this->provider->supports($token));
    }

    public function testIsSupportWithoutFirewallNameAttribute()
    {
        $token = new Token(new User(), 'asd', self::PROVIDER_KEY);
        $token->setAttribute('nonce', base64_encode(uniqid(self::TEST_NONCE)));
        $token->setAttribute('created', date('Y-m-d H:i:s'));
        $this->assertFalse($this->provider->supports($token));
    }

    public function testIsSupportWithNotSupportedFirewallNameAttribute()
    {
        $token = new Token(new User(), 'asd', self::PROVIDER_KEY);
        $token->setAttribute('firewallName', 'notSupported');
        $token->setAttribute('nonce', base64_encode(uniqid(self::TEST_NONCE)));
        $token->setAttribute('created', date('Y-m-d H:i:s'));
        $this->provider->setFirewallName('test');
        $this->assertFalse($this->provider->supports($token));
    }

    public function testIsSupport()
    {
        $token = new Token(new User(), 'asd', self::PROVIDER_KEY);
        $token->setAttribute('firewallName', 'test');
        $token->setAttribute('nonce', base64_encode(uniqid(self::TEST_NONCE)));
        $token->setAttribute('created', date('Y-m-d H:i:s'));
        $this->provider->setFirewallName('test');
        $this->assertTrue($this->provider->supports($token));
    }

    /**
     * @param User $user
     * @param string $secret
     * @param string $salt
     *
     * @return Token
     */
    protected function prepareTestInstance($user, $secret, $salt = '')
    {
        $this->userProvider
            ->expects($this->any())
            ->method('loadUserByUsername')
            ->will($this->returnValue($user));

        $nonce = base64_encode(uniqid(self::TEST_NONCE));
        $time = date('Y-m-d H:i:s');

        $digest = $this->encoder->encodePassword(
            sprintf(
                '%s%s%s',
                base64_decode($nonce),
                $time,
                $secret
            ),
            $salt
        );

        $token = new Token($user, $digest, self::PROVIDER_KEY, $user->getRoles());
        $token->setAttribute('digest', $digest);
        $token->setAttribute('nonce', $nonce);
        $token->setAttribute('created', $time);
        $token->setAttribute('firewallName', 'test');
        $this->provider->setFirewallName('test');

        return $token;
    }
}
