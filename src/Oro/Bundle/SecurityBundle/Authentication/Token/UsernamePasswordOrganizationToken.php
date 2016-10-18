<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class UsernamePasswordOrganizationToken extends UsernamePasswordToken implements OrganizationContextTokenInterface
{
    use OrganizationContextTokenSerializerTrait;

    /**
     * @param string       $user
     * @param string       $credentials
     * @param string       $providerKey
     * @param array        $roles
     * @param Organization $organizationContext
     */
    public function __construct($user, $credentials, $providerKey, Organization $organizationContext, array $roles = [])
    {
        $this->setOrganizationContext($organizationContext);
        parent::__construct($user, $credentials, $providerKey, $roles);
    }
}
