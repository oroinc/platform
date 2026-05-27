<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Event;

/**
 * Dispatched before fields are synchronized from a draft back to the original entity.
 */
class EntityFromDraftSyncBeforeEvent extends EntityDraftSyncEvent
{
}
