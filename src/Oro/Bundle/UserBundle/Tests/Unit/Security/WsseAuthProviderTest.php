<?php

namespace Oro\Bundle\UserBundle\Tests\Security;

use Doctrine\Common\Cache\ArrayCache;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

use Escape\WSSEAuthenticationBundle\Security\Core\Authentication\Token\Token;

use Oro\Bundle\UserBundle\Security\WsseAuthProvider;

class WsseAuthProviderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_SALT     = 'someSalt';
    const TEST_PASSWORD = 'somePassword';
    const TEST_NONCE    = 'someNonce';
    const TEST_API_KEY  = 'someApiKey';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $userProvider;

    /** @var MessageDigestPasswordEncoder */
    protected $encoder;

    /** @var WsseAuthProvider */
    protected $provider;

    public function setUp()
    {
        $this->userProvider = $this->getMock('Symfony\Component\Security\Core\User\UserProviderInterface');
        $this->encoder      = new MessageDigestPasswordEncoder('sha1', true, 1);
        $cache              = new ArrayCache();

        $this->provider = new WsseAuthProvider($this->userProvider, $this->encoder, $cache);
    }

    public function tearDown()
    {
        unset($this->userProvider, $this->encoder, $this->provider);
    }

    /**
     * @dataProvider userProvider
     *
     * @param UserInterface $user
     * @param               $secret
     * @param string        $salt
     */
    public function testOverridesLogic(UserInterface $user, $secret, $salt = '')
    {
        $this->userProvider->expects($this->once())->method('loadUserByUsername')
            ->will($this->returnValue($user));

        $nonce = base64_encode(uniqid(self::TEST_NONCE));
        $time  = date('Y-m-d H:i:s');

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
        $regularUser->expects($this->once())->method('getPassword')->will($this->returnValue(self::TEST_PASSWORD));
        $regularUser->expects($this->once())->method('getSalt')->will($this->returnValue(self::TEST_SALT));
        $regularUser->expects($this->any())->method('getRoles')->will($this->returnValue([]));

        $advancedUser = $this->getMock('Oro\Bundle\UserBundle\Security\AdvancedApiUserInterface');
        $advancedUser->expects($this->once())->method('getApiKey')->will($this->returnValue(self::TEST_API_KEY));
        $advancedUser->expects($this->never())->method('getPassword');
        $advancedUser->expects($this->never())->method('getSalt');
        $advancedUser->expects($this->any())->method('getRoles')->will($this->returnValue([]));

        return [
            'regular user given, should use password and salt' => [$regularUser, self::TEST_PASSWORD, self::TEST_SALT],
            'advanced user given, should take API key only'    => [$advancedUser, self::TEST_API_KEY]
        ];
    }
}
