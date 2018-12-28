<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;

class OrganizationTokenTest extends \PHPUnit\Framework\TestCase
{
    use OrganizationContextTrait;

    public function testOrganizationContextSerialization(): void
    {
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 3]);

        $token = new OrganizationToken($organization);

        $this->assertOrganizationContextSerialization($token);
    }
}
