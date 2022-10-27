<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;

/**
 * The authentication token for "remember me" feature.
 */
class OrganizationRememberMeToken extends RememberMeToken implements
    OrganizationAwareTokenInterface,
    RolesAwareTokenInterface
{
    use RolesAndOrganizationAwareTokenTrait;

    /**
     * @param UserInterface $user
     * @param string $providerKey
     * @param string $key
     * @param Organization $organization
     */
    public function __construct(UserInterface $user, $providerKey, $key, $organization)
    {
        parent::__construct($user, $providerKey, $key);
        $this->initRoles($user->getUserRoles());
        $this->setOrganization($organization);
    }
}
