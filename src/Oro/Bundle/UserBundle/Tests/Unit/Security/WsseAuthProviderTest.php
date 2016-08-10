<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

use Escape\WSSEAuthenticationBundle\Security\Core\Authentication\Token\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Unit\Fixture\RegularUser;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Security\WsseAuthProvider;
use Oro\Bundle\UserBundle\Security\WsseTokenFactory;

class WsseAuthProviderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_SALT = 'someSalt';
    const TEST_PASSWORD = 'somePassword';
    const TEST_NONCE = 'someNonce';
    const TEST_API_KEY = 'someApiKey';

    /** @var \PHPUnit_Framework_MockObject_MockObject|UserProviderInterface */
    protected $userProvider;

    /** @var MessageDigestPasswordEncoder */
    protected $encoder;

    /** @var WsseAuthProvider */
    protected $provider;

    protected function setUp()
    {
        $this->userProvider = $this->getMock('Symfony\Component\Security\Core\User\UserProviderInterface');
        $this->encoder = new MessageDigestPasswordEncoder('sha1', true, 1);
        $cache = new ArrayCache();

        $this->provider = new WsseAuthProvider($this->userProvider, $this->encoder, $cache);
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
        $provider = new WsseAuthProvider($this->userProvider, $this->encoder, new ArrayCache());
        $provider->authenticate(new Token());
    }

    /**
     * @dataProvider userProvider
     *
     * @param object $user
     * @param string $secret
     * @param string $salt
     */
    public function testAuthenticateOnCorrectData($user, $secret, $salt = '')
    {
        $token = $this->prepareTestInstance($user, $secret, $salt);
        $this->assertFalse($token->isAuthenticated());

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
        $regularUser->setRoles([]);

        $organization = new Organization();
        $organization->setEnabled(true);

        $userApiKey = new UserApi();
        $userApiKey->setApiKey(self::TEST_API_KEY);
        $userApiKey->setOrganization($organization);

        $advancedUser = new User();
        $advancedUser->addOrganization($organization);
        $advancedUser->addApiKey($userApiKey);
        $advancedUser->setEnabled(true);
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
    public function testAuthenticateOnWrongData($user, $secret, $exceptionType, $exceptionString)
    {
        $token = $this->prepareTestInstance($user, $secret);
        $this->assertFalse($token->isAuthenticated());

        $this->setExpectedException($exceptionType, $exceptionString);

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

        $user = new User();
        $user->addOrganization($disabledOrganization);
        $user->addApiKey($userApiKey);
        $user->setEnabled(true);
        $userApiKey->setUser($user);

        $org1ApiKey = new UserApi();
        $org1ApiKey->setApiKey(self::TEST_API_KEY);
        $org1ApiKey->setOrganization($organization1);

        $userWithWrongKey = new User();
        $userWithWrongKey->addOrganization($organization2);
        $userWithWrongKey->addApiKey($org1ApiKey);
        $userWithWrongKey->setEnabled(true);

        $org2ApiKey = new UserApi();
        $org2ApiKey->setApiKey(self::TEST_API_KEY);
        $org2ApiKey->setOrganization($organization2);

        $disabledUser = new User();
        $disabledUser->addOrganization($organization2);
        $disabledUser->addApiKey($org2ApiKey);
        $disabledUser->setEnabled(false);

        return [
            'disabled organization' => [
                $user, self::TEST_API_KEY, BadCredentialsException::class, 'Organization is not active.'
            ],
            'wrong API key' => [
                $user, 'wrong key', AuthenticationException::class, 'WSSE authentication failed.'
            ],
            'API key from another organization' => [
                $userWithWrongKey, self::TEST_API_KEY, BadCredentialsException::class, 'Wrong API key.'
            ],
            'disabled user' => [
                $disabledUser, self::TEST_API_KEY, BadCredentialsException::class, 'User is not active.'
            ]
        ];
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function testGetSecret()
    {
        $noApiKeyUser = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $noApiKeyUser
            ->expects(static::exactly(2))
            ->method('getApiKeys')
            ->will(static::returnValue(new ArrayCollection()));

        $noApiKeyUser->expects(static::never())->method('getPassword');
        $noApiKeyUser->expects(static::never())->method('getSalt');
        $noApiKeyUser->expects(static::any())->method('getRoles')->will(static::returnValue([]));
        $noApiKeyUser->expects(static::once())->method('isEnabled')->willReturn(true);

        $this->userProvider
            ->expects(static::exactly(2))
            ->method('loadUserByUsername')
            ->will(static::returnValue($noApiKeyUser));

        $nonce = base64_encode(uniqid(self::TEST_NONCE));
        $time = date('Y-m-d H:i:s');

        $digest = $this->encoder->encodePassword(
            sprintf('%s%s%s', base64_decode($nonce), $time, ''),
            ''
        );

        $token = new Token();
        $token->setAttribute('digest', $digest);
        $token->setAttribute('nonce', $nonce);
        $token->setAttribute('created', $time);

        $this->provider->authenticate($token);
    }

    /**
     * @param $user
     * @param $secret
     * @param $salt
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

        $token = new Token();
        $token->setAttribute('digest', $digest);
        $token->setAttribute('nonce', $nonce);
        $token->setAttribute('created', $time);

        return $token;
    }
}
