<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * The authentication token that is used for an user impersonation.
 */
class ImpersonationToken extends AbstractToken implements OrganizationAwareTokenInterface, RolesAwareTokenInterface
{
    use RolesAndOrganizationAwareTokenTrait;

    public function __construct(AbstractUser $user, Organization $organization, array $roles = [])
    {
        parent::__construct($this->initRoles($roles));

        $this->setUser($user);
        $this->setOrganization($organization);
    }
}
