<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Model\Role;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * The organization aware authentication token.
 */
class OrganizationToken extends AbstractToken implements OrganizationAwareTokenInterface, RolesAwareTokenInterface
{
    use RolesAndOrganizationAwareTokenTrait;

    /**
     * @param Organization    $organization
     * @param Role[]|string[] $roles
     */
    public function __construct(Organization $organization, array $roles = [])
    {
        parent::__construct($this->initRoles($roles));

        $this->setOrganization($organization);
        $this->setAuthenticated(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return ''; // anonymous credentials
    }
}
