<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\ImpersonationToken;
use Oro\Bundle\UserBundle\Entity\User;

class ImpersonationTokenTest extends \PHPUnit\Framework\TestCase
{
    use OrganizationContextTrait;

    public function testOrganizationContextSerialization(): void
    {
        /** @var User $user */
        $user = $this->getEntity(User::class, ['id' => 1]);

        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 5]);

        $token = new ImpersonationToken($user, $organization);

        $this->assertOrganizationContextSerialization($token);
    }
}
