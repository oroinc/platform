<?php

namespace Oro\Bundle\SyncBundle\Security\Token;

use Oro\Bundle\SecurityBundle\Authentication\Token\AuthenticatedTokenTrait;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenTrait;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * The authentication token for Sync authentication ticket.
 */
class TicketToken extends UsernamePasswordToken implements OrganizationAwareTokenInterface
{
    use AuthenticatedTokenTrait;
    use OrganizationAwareTokenTrait;
}
