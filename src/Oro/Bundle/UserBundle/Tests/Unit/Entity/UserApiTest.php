<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Entity\User;

class UserApiTest extends \PHPUnit_Framework_TestCase
{
    public function testApi()
    {
        $api  = $this->getApi();
        $user = new User();

        $this->assertEmpty($api->getId());

        $api->setUser($user);

        $this->assertEquals($user, $api->getUser());
    }

    public function testKey()
    {
        $api  = $this->getApi();
        $key  = $api->generateKey();

        $this->assertNotEmpty($key);

        $api->setApiKey($key);

        $this->assertEquals($key, $api->getApiKey());
    }

    protected function setUp()
    {
        $this->api = new UserApi();
    }

    /**
     * @return UserApi
     */
    protected function getApi()
    {
        return $this->api;
    }

    public function testOrganization()
    {
        $this->assertNull($this->api->getOrganization());
        $value = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        $this->assertEquals($this->api, $this->api->setOrganization($value));
        $this->assertEquals($value, $this->api->getOrganization());
    }
}
