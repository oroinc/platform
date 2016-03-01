<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

use Escape\WSSEAuthenticationBundle\Security\Core\Authentication\Token\Token;

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
    public function testOverridesLogic($user, $secret, $salt = '')
    {
        $this->userProvider
            ->expects($this->exactly(2))
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

        $this->provider->authenticate($token);
    }

    /**
     * @return array
     */
    public function userProvider()
    {
        $regularUser = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');
        $regularUser->expects($this->exactly(2))->method('getPassword')->will($this->returnValue(self::TEST_PASSWORD));
        $regularUser->expects($this->once())->method('getSalt')->will($this->returnValue(self::TEST_SALT));
        $regularUser->expects($this->any())->method('getRoles')->will($this->returnValue([]));

        $userApiKey = new UserApi();
        $userApiKey->setApiKey(self::TEST_API_KEY);
        $userApiKeys = new ArrayCollection([$userApiKey]);

        $advancedUser = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $advancedUser
            ->expects($this->exactly(2))
            ->method('getApiKeys')
            ->will($this->returnValue($userApiKeys));
        $advancedUser->expects($this->never())->method('getPassword');
        $advancedUser->expects($this->never())->method('getSalt');
        $advancedUser->expects($this->any())->method('getRoles')->will($this->returnValue([]));

        return [
            'regular user given, should use password and salt' => [$regularUser, self::TEST_PASSWORD, self::TEST_SALT],
            'advanced user given, should take API key only' => [$advancedUser, $userApiKeys]
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
}
