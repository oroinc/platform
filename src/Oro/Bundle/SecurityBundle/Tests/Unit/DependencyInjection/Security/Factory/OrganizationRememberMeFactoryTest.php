<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Security\Factory;

use Oro\Bundle\SecurityBundle\DependencyInjection\Security\Factory\OrganizationRememberMeFactory;

class OrganizationRememberMeFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var OrganizationRememberMeFactory
     */
    protected $factory;

    public function setUp()
    {
        $this->factory = new OrganizationRememberMeFactory();
    }

    public function testKey()
    {
        $this->assertEquals('organization-remember-me', $this->factory->getKey());
    }
}
