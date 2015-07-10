<?php

namespace OroCRM\Bundle\ChannelBundle\Tests\Unit\Provider;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\UserBundle\Entity\User;
use OroCRM\Bundle\ChannelBundle\Provider\UserChannelTokenProvider;

class UserChannelTokenProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserChannelTokenProvider
     */
    protected $tokenProvider;

    protected function setUp()
    {
        $this->tokenProvider = new UserChannelTokenProvider();
    }

    protected function getTokenProvider()
    {
        return new UserChannelTokenProvider();
    }

    public function testGetToken()
    {
        $user = new User(2);
        $user->setPlainPassword('qa123123');
        $token = $this->tokenProvider->getToken($user);
        $this->assertSame($token, $this->getTokenProvider()->getToken($user));
    }
}
