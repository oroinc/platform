<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The authentication token for "remember me" feature.
 */
class OrganizationRememberMeToken extends RememberMeToken implements OrganizationAwareTokenInterface
{
    use AuthenticatedTokenTrait;
    use OrganizationAwareTokenTrait;

    /**
     * @param UserInterface $user
     * @param string        $providerKey
     * @param string        $key
     * @param Organization  $organization
     */
    public function __construct(UserInterface $user, $providerKey, $key, $organization)
    {
        parent::__construct($user, $providerKey, $key);
        $this->setOrganization($organization);
    }
}
