<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token;

use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;

class UsernamePasswordOrganizationTokenTest extends OrganizationTokenTestAbstract
{
    use OrganizationContextTrait;

    /**
     * {@inheritdoc}
     */
    protected function getToken()
    {
        $user = new User(2);
        $organization = new Organization(3);
        return new UsernamePasswordOrganizationToken($user, ['test'], 'key', $organization);
    }

    public function testOrganizationContextSerialization(): void
    {
        $token = $this->getToken();

        $this->assertOrganizationContextSerialization($token);
    }
}
