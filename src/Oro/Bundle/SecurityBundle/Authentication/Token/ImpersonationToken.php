<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * Token user impersonation.
 */
class ImpersonationToken extends AbstractToken implements OrganizationContextTokenInterface
{
    use OrganizationContextTokenSerializerTrait;
    /**
     * @param string|object            $user         The username (like a nickname, email address, etc.),
     *                                               or a UserInterface instance
     *                                               or an object implementing a __toString method.
     * @param Organization             $organization The organization
     * @param RoleInterface[]|string[] $roles        An array of roles
     */
    public function __construct($user, Organization $organization, array $roles = [])
    {
        parent::__construct($roles);

        $this->setUser($user);
        $this->setOrganizationContext($organization);
        parent::setAuthenticated(count($roles) > 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return $this->getUsername();
    }
}
