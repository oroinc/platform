<?php

namespace Oro\Bundle\SyncBundle\Security\Token;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenSerializerTrait;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

/**
 * Anonymous security token for Sync authentication ticket.
 */
class AnonymousTicketToken extends AnonymousToken implements OrganizationContextTokenInterface
{
    use OrganizationContextTokenSerializerTrait;
}
