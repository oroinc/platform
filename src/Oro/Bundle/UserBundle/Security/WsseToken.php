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

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        $objectSerialize = serialize($this->organization);
        $string = implode('||', array($objectSerialize, parent::serialize()));
        return $string;
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($organizationData, $parentStr) = explode('||', $serialized);
        $this->organization = unserialize($organizationData);
        parent::unserialize($parentStr);
    }
}
