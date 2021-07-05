<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * The authentication token that is used when an user uses a username and password to authentication.
 */
class UsernamePasswordOrganizationToken extends UsernamePasswordToken implements
    OrganizationAwareTokenInterface,
    RolesAwareTokenInterface
{
    use RolesAndOrganizationAwareTokenTrait;

    /**
     * @param string       $user
     * @param string       $credentials
     * @param string       $providerKey
     * @param Organization $organization
     * @param array        $roles
     */
    public function __construct($user, $credentials, $providerKey, Organization $organization, array $roles = [])
    {
        parent::__construct($user, $credentials, $providerKey, $this->initRoles($roles));
        $this->setOrganization($organization);
    }
}
