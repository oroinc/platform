<?php

namespace Oro\Bundle\ApiBundle\Batch\Model;

use Oro\Bundle\ApiBundle\Model\Error;

/**
 * Represents an error occurred when processing a batch operation.
 */
final class BatchError extends Error
{
    /** @var string|null */
    private $id;

    /** @var int|null */
    private $itemIndex;

    /**
     * Gets an unique identifier of this error.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Sets an unique identifier of this error.
     *
     * @param string|null $id
     *
     * @return $this
     */
    public function setId(?string $id): BatchError
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
     *
     * @param int|null $itemIndex
     *
     * @return $this
     */
    public function setItemIndex(?int $itemIndex): BatchError
    {
        $this->itemIndex = $itemIndex;

        return $this;
    }
}
