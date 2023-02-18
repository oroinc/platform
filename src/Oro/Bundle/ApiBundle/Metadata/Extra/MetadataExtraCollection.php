<?php

namespace Oro\Bundle\ApiBundle\Metadata\Extra;

/**
 * The collection of requests for additional metadata info.
 */
class MetadataExtraCollection
{
    /** @var MetadataExtraInterface[] */
    private array $extras = [];

    /**
     * Indicates whether the collection is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->extras);
    }

    /**
     * Gets a list of requests for additional metadata info.
     *
     * @return MetadataExtraInterface[]
     */
    public function getMetadataExtras(): array
    {
        return $this->extras;
    }

    /**
     * Sets a list of requests for additional metadata info.
     *
     * @param MetadataExtraInterface[] $extras
     *
     * @throws \InvalidArgumentException if $extras has invalid elements
     */
    public function setMetadataExtras(array $extras): void
    {
        foreach ($extras as $extra) {
            if (!$extra instanceof MetadataExtraInterface) {
                throw new \InvalidArgumentException(sprintf(
                    'Expected an array of "%s".',
                    MetadataExtraInterface::class
                ));
            }
        }
        $this->extras = array_values($extras);
    }

    /**
     * Checks whether some additional metadata info is requested.
     */
    public function hasMetadataExtra(string $extraName): bool
    {
        foreach ($this->extras as $extra) {
            if ($extra->getName() === $extraName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets a request for some additional metadata info by its name.
     */
    public function getMetadataExtra(string $extraName): ?MetadataExtraInterface
    {
        foreach ($this->extras as $extra) {
            if ($extra->getName() === $extraName) {
                return $extra;
            }
        }

        return null;
    }

    /**
     * Adds a request for some additional metadata info.
     *
     * @throws \InvalidArgumentException if a metadata extra with the same name already exists
     */
    public function addMetadataExtra(MetadataExtraInterface $extra): void
    {
        if ($this->hasMetadataExtra($extra->getName())) {
            throw new \InvalidArgumentException(sprintf(
                'The "%s" metadata extra already exists.',
                $extra->getName()
            ));
        }
        $this->extras[] = $extra;
    }

    /**
     * Removes a request for some additional metadata info.
     */
    public function removeMetadataExtra(string $extraName): void
    {
        $keys = array_keys($this->extras);
        foreach ($keys as $key) {
            if ($this->extras[$key]->getName() === $extraName) {
                unset($this->extras[$key]);
            }
        }
        $this->extras = array_values($this->extras);
    }
}
