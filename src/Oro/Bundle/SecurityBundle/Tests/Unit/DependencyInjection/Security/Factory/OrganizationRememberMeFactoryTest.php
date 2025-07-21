<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Security\Factory;

use Oro\Bundle\SecurityBundle\DependencyInjection\Security\Factory\OrganizationRememberMeFactory;
use PHPUnit\Framework\TestCase;

class OrganizationRememberMeFactoryTest extends TestCase
{
    private OrganizationRememberMeFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new OrganizationRememberMeFactory();
    }

    public function testKey(): void
    {
        $this->assertEquals('organization-remember-me', $this->factory->getKey());
    }
}
