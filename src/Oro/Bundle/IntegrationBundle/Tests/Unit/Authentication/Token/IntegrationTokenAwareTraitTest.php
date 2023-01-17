<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Authentication\Token;

use Oro\Bundle\IntegrationBundle\Authentication\Token\IntegrationTokenAwareTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class IntegrationTokenAwareTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testTraitIfTokenIsNull(): void
    {
        $class = new class() {
            use IntegrationTokenAwareTrait;

            public function __construct()
            {
                $this->tokenStorage = new TokenStorage();
            }
        };

        $organization = new Organization();
        $organization->setId(1);
        $integration = new Channel();
        $integration->setOrganization($organization);

        self::assertNull(ReflectionUtil::getPropertyValue($class, 'tokenStorage')->getToken());

        ReflectionUtil::callMethod($class, 'setTemporaryIntegrationToken', [$integration]);
        
        self::assertEquals(
            $organization,
            ReflectionUtil::getPropertyValue($class, 'tokenStorage')->getToken()->getOrganization()
        );
    }
}
