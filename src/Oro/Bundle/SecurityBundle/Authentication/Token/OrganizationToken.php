<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Role\RoleInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class OrganizationToken extends AbstractToken implements OrganizationContextTokenInterface
{
    /** @var Organization */
    private $organization;

    /**
     * @param Organization             $organization The organization
     * @param RoleInterface[]|string[] $roles        An array of roles
     */
    public function __construct(Organization $organization, array $roles = [])
    {
        parent::__construct($roles);

        $this->setOrganizationContext($organization);
        parent::setAuthenticated(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return ''; // anonymous credentials
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
