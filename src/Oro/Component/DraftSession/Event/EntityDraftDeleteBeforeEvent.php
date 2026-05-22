<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Event;

use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched before a draft entity is deleted.
 */
class EntityDraftDeleteBeforeEvent extends Event
{
    public function __construct(
        private readonly EntityDraftAwareInterface $draft,
    ) {
    }

    public function getDraft(): EntityDraftAwareInterface
    {
        return $this->draft;
    }
}
