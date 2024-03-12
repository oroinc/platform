<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The authentication token that is used when an user uses a username and password to authentication.
 */
class UsernamePasswordOrganizationToken extends UsernamePasswordToken implements
    OrganizationAwareTokenInterface,
    RolesAwareTokenInterface
{
    use RolesAndOrganizationAwareTokenTrait;

    public function __construct(
        UserInterface $user,
        string $firewallName,
        Organization $organization,
        array $roles = []
    ) {
        parent::__construct($user, $firewallName, $this->initRoles($roles));
        $this->setOrganization($organization);
    }
}
