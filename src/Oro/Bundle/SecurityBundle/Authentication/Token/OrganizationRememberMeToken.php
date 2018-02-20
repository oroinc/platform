<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\User\UserInterface;

class OrganizationRememberMeToken extends RememberMeToken implements OrganizationContextTokenInterface
{
    use OrganizationContextTokenSerializerTrait;

    /**
     * @param UserInterface $user
     * @param string        $providerKey
     * @param string        $key
     * @param Organization $organizationContext
     */
    public function __construct(UserInterface $user, $providerKey, $key, $organizationContext)
    {
        $this->setOrganizationContext($organizationContext);
        parent::__construct($user, $providerKey, $key);
    }
}
