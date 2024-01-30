<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Authentication\Token;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Stub\IntegrationTokenAware;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;

class IntegrationTokenAwareTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testTraitIfTokenIsNull(): void
    {
        $class = new IntegrationTokenAware();

        $organization = new Organization();
        $organization->setId(1);

        $integration = new Channel();
        $integration->setOrganization($organization);

        self::assertNull(ReflectionUtil::getPropertyValue($class, 'tokenStorage')->getToken());

        ReflectionUtil::callMethod($class, 'setTemporaryIntegrationToken', [$integration]);

        /** @var OrganizationToken $token */
        $token = ReflectionUtil::getPropertyValue($class, 'tokenStorage')->getToken();

        self::assertEquals($organization, $token->getOrganization());
    }

    public function testTraitIfTokenIsNullWithUser(): void
    {
        $class = new IntegrationTokenAware();

        $organization = new Organization();
        $organization->setId(1);

        $user = new User();
        $user->setId(2);

        $integration = new Channel();
        $integration->setOrganization($organization);

        self::assertNull(ReflectionUtil::getPropertyValue($class, 'tokenStorage')->getToken());

        ReflectionUtil::callMethod($class, 'setTemporaryIntegrationTokenWithUser', [$integration, $user]);

        /** @var OrganizationToken $token */
        $token = ReflectionUtil::getPropertyValue($class, 'tokenStorage')->getToken();

        self::assertEquals($organization, $token->getOrganization());
        self::assertEquals($user, $token->getUser());
    }
}
