<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Event;

use Doctrine\Common\Util\ClassUtils;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Base event for draft synchronization between two draft-aware entities.
 *
 * Not dispatched directly — use direction-specific subclasses:
 * {@see EntityToDraftSyncEvent} and {@see EntityFromDraftSyncEvent}.
 */
class EntityDraftSyncEvent extends Event
{
    public function __construct(
        private readonly EntityDraftAwareInterface $source,
        private readonly EntityDraftAwareInterface $target,
    ) {
    }

    public function getSource(): EntityDraftAwareInterface
    {
        return $this->source;
    }

    public function getTarget(): EntityDraftAwareInterface
    {
        return $this->target;
    }

    public function getEntityClass(): string
    {
        return ClassUtils::getClass($this->source);
    }
}
