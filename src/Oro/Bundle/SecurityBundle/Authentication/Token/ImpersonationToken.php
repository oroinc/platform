<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Role\RoleInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class ImpersonationToken extends AbstractToken implements OrganizationContextTokenInterface
{
    /** @var Organization */
    private $organization;

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

    /**
     * {@inheritdoc}
     */
    public function getOrganizationContext()
    {
        return $this->organization;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrganizationContext(Organization $organization)
    {
        $this->organization = $organization;
    }
}
