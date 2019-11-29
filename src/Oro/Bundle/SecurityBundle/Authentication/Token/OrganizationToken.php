<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Role\Role;

/**
 * The organization aware authentication token.
 */
class OrganizationToken extends AbstractToken implements OrganizationAwareTokenInterface
{
    use AuthenticatedTokenTrait;
    use OrganizationAwareTokenTrait;

    /**
     * @param Organization    $organization
     * @param Role[]|string[] $roles
     */
    public function __construct(Organization $organization, array $roles = [])
    {
        parent::__construct($roles);

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
