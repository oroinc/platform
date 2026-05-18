<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Event;

use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Base entity draft persist event.
 *
 * Not dispatched directly, see {@see EntityDraftPersistBeforeEvent} and {@see EntityDraftPersistAfterEvent}
 */
class EntityDraftPersistEvent extends Event
{
    public function __construct(
        private readonly EntityDraftAwareInterface $draft,
        private readonly ?EntityDraftAwareInterface $entity = null,
    ) {
    }

    public function getDraft(): EntityDraftAwareInterface
    {
        return $this->draft;
    }

    public function getEntity(): ?EntityDraftAwareInterface
    {
        return $this->entity;
    }
}
