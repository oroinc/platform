<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\UserLoggingInfoProvider;

class UserLoggingInfoProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserLoggingInfoProvider */
    protected $provider;

    public function setUp()
    {
        $this->provider = new UserLoggingInfoProvider();
    }

    public function testGetUserLoggingInfo()
    {
        $user = new User();
        $user->setEnabled(true);
        $user->setUsername('john');
        $user->setEmail('john@example.com');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setLastLogin(new \DateTime('01-01-2010'));
        $user->setCreatedAt(new \DateTime('01-01-2000'));

        $this->assertEquals([
            'id' => null,
            'username' => 'john',
            'email' => 'john@example.com',
            'fullname' => 'John Doe',
            'enabled' => true,
            'lastlogin' => new \DateTime('01-01-2010'),
            'createdat' => new \DateTime('01-01-2000'),
        ], $this->provider->getUserLoggingInfo($user));
    }
}
