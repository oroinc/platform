<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Security;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\RolesAndOrganizationAwareTokenTrait;
use Oro\Bundle\SecurityBundle\Authentication\Token\RolesAwareTokenInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * The WSSE authentication token.
 */
class WsseToken extends UsernamePasswordToken implements OrganizationAwareTokenInterface, RolesAwareTokenInterface
{
    use RolesAndOrganizationAwareTokenTrait;

    public function __construct(AbstractUser $user, string $firewallName, array $roles = [])
    {
        parent::__construct($user, $firewallName, $this->initRoles($roles));
    }
}
