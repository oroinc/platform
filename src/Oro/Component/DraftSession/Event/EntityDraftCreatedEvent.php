<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Event;

use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after a draft entity is created from an original entity.
 */
class EntityDraftCreatedEvent extends Event
{
    public function __construct(
        private readonly EntityDraftAwareInterface $entity,
        private readonly EntityDraftAwareInterface $draft,
    ) {
    }

    public function getEntity(): EntityDraftAwareInterface
    {
        return $this->entity;
    }

    public function getDraft(): EntityDraftAwareInterface
    {
        return $this->draft;
    }
}
