<?php

namespace Oro\Bundle\SyncBundle\Security\Token;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\RolesAndOrganizationAwareTokenTrait;
use Oro\Bundle\SecurityBundle\Authentication\Token\RolesAwareTokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * The authentication token for Sync authentication ticket.
 */
class TicketToken extends UsernamePasswordToken implements OrganizationAwareTokenInterface, RolesAwareTokenInterface
{
    use RolesAndOrganizationAwareTokenTrait;

    public function __construct($user, string $firewallName, array $roles = [])
    {
        parent::__construct($user, $firewallName, $this->initRoles($roles));
    }
}
