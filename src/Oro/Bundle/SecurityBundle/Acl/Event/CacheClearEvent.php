<?php

namespace Oro\Bundle\SecurityBundle\Acl\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired by the AclCache during removing all ACLs from the cache.
 */
class CacheClearEvent extends Event
{
    public const CACHE_CLEAR_EVENT = 'oro_security.acl_cache.clear';
}
