<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeToken;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;

class OrganizationRememberMeTokenTest extends OrganizationTokenTestAbstract
{
    use OrganizationContextTrait;

    protected function getToken()
    {
        $user = new User(2);
        $organization = new Organization(3);
        return new OrganizationRememberMeToken($user, 'provider', 'key', $organization);
    }

    public function testOrganizationContextSerialization(): void
    {
        $token = $this->getToken();

        $this->assertOrganizationContextSerialization($token);
    }
}
