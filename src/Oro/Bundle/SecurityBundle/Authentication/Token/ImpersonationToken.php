<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Model\Role;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * The authentication token that is used for an user impersonation.
 */
class ImpersonationToken extends AbstractToken implements OrganizationAwareTokenInterface, RolesAwareTokenInterface
{
    use RolesAndOrganizationAwareTokenTrait;

    /**
     * @param string|object            $user         The username (like a nickname, email address, etc.),
     *                                               or a UserInterface instance
     *                                               or an object implementing a __toString method.
     * @param Organization             $organization The organization
     * @param Role[]|string[]          $roles        An array of roles
     */
    public function __construct($user, Organization $organization, array $roles = [])
    {
        parent::__construct($this->initRoles($roles));

        $this->setUser($user);
        $this->setOrganization($organization);
        $this->setAuthenticated(count($roles) > 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return $this->getUsername();
    }
}
