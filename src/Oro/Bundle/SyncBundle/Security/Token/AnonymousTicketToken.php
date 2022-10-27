<?php

namespace Oro\Bundle\SyncBundle\Security\Token;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenTrait;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

/**
 * The anonymous authentication token for Sync authentication ticket.
 */
class AnonymousTicketToken extends AnonymousToken implements OrganizationAwareTokenInterface
{
    use OrganizationAwareTokenTrait;
}
