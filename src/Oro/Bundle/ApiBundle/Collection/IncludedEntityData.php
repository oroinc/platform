<?php

namespace Oro\Bundle\ApiBundle\Collection;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Symfony\Component\Form\FormInterface;

/**
 * A storage for detailed information about an additional entity included into API request
 * for such actions as "create", "update", "update_subresource", etc.
 */
class IncludedEntityData
{
    private string $path;
    private int $index;
    private bool $existing;
    private ?array $normalizedData = null;
    private ?EntityMetadata $metadata = null;
    private ?FormInterface $form = null;

    /**
     * @param string $path     A path to the entity in the request data
     * @param int    $index    An index of the entity in the included data
     * @param bool   $existing TRUE if an existing entity should be updated;
     *                         FALSE if a new entity should be created
     */
    public function __construct(string $path, int $index, bool $existing = false)
    {
        $this->path = $path;
        $this->index = $index;
        $this->existing = $existing;
    }

    /**
     * Gets a path to the entity in the request data.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Gets an index of the entity in the included data.
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * Gets a value indicates whether an existing entity should be updated or new one should be created.
     */
    public function isExisting(): bool
    {
        return $this->existing;
    }

    /**
     * Gets a normalized representation of the entity.
     */
    public function getNormalizedData(): ?array
    {
        return $this->normalizedData;
    }

    /**
     * Sets a normalized representation of the entity.
     */
    public function setNormalizedData(?array $normalizedData): void
    {
        $this->normalizedData = $normalizedData;
    }

    /**
     * Gets metadata of the entity.
     */
    public function getMetadata(): ?EntityMetadata
    {
        return $this->metadata;
    }

    /**
     * Sets metadata of the entity.
     */
    public function setMetadata(?EntityMetadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * Gets the form that is used to transform entity data.
     */
    public function getForm(): ?FormInterface
    {
        return $this->form;
    }

    /**
     * Sets the form that is used to transform entity data.
     */
    public function setForm(?FormInterface $form): void
    {
        $this->form = $form;
    }
}
