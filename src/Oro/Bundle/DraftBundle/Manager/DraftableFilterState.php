<?php

namespace Oro\Bundle\DraftBundle\Manager;

/**
 * State for disabled draftable filters.
 */
class DraftableFilterState
{
    private bool $enabled = true;

    public function isDisabled(): bool
    {
        return !$this->enabled;
    }

    public function setDisabled(): void
    {
        $this->enabled = false;
    }
}
