<?php

namespace Oro\Bundle\ApiBundle\Batch\Model;

use Oro\Bundle\ApiBundle\Model\Error;

/**
 * Represents an error occurred when processing a batch operation.
 */
final class BatchError extends Error
{
    private ?string $id = null;
    private ?int $itemIndex = null;

    /**
     * Gets an unique identifier of this error.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Sets an unique identifier of this error.
     */
    public function setId(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Gets the index of the source record caused this error.
     */
    public function getItemIndex(): ?int
    {
        return $this->itemIndex;
    }

    /**
     * Sets the index of the source record caused this error.
     */
    public function setItemIndex(?int $itemIndex): self
    {
        $this->itemIndex = $itemIndex;

        return $this;
    }
}
