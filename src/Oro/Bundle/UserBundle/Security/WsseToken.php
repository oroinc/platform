<?php

namespace Oro\Bundle\UserBundle\Security;

use Escape\WSSEAuthenticationBundle\Security\Core\Authentication\Token\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

class WsseToken extends Token implements OrganizationContextTokenInterface
{
    /** @var  Organization */
    protected $organization;

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
