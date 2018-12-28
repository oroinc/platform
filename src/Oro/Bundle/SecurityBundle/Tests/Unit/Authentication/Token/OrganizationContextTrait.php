<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * Provides assertion method for OrganizationContextTokenInterface serialization check.
 */
trait OrganizationContextTrait
{
    use EntityTrait;

    public function assertOrganizationContextSerialization(OrganizationContextTokenInterface $token): void
    {
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 3]);
        $token->setOrganizationContext($organization);

        /** @var OrganizationContextTokenInterface $newToken */
        $newToken = unserialize(serialize($token));

        self::assertNotNull($newToken->getOrganizationContext());
        self::assertEquals($token->getOrganizationContext()->getId(), $newToken->getOrganizationContext()->getId());
    }
}
