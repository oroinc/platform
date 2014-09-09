<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 9/9/14
 * Time: 11:08 PM
 */

namespace Oro\Bundle\UserBundle\Security;


use Escape\WSSEAuthenticationBundle\Security\Core\Authentication\Token\Token;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

class WsseToken extends Token implements OrganizationContextTokenInterface
{
    protected $organization;

    /**
     * Returns organization
     *
     * @return Organization
     */
    public function getOrganizationContext()
    {
        return $this->organization;
    }

    /**
     * Set an organization
     *
     * @param Organization $organization
     */
    public function setOrganizationContext(Organization $organization)
    {
        $this->organization = $organization;
    }
}
