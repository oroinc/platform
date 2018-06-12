<?php

namespace Oro\Bundle\SyncBundle\Security\Token;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenSerializerTrait;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Security token for Sync authentication ticket.
 */
class TicketToken extends UsernamePasswordToken implements OrganizationContextTokenInterface
{
    use OrganizationContextTokenSerializerTrait;
}
