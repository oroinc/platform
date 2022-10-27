<?php

namespace Oro\Bundle\LayoutBundle\Cache\Metadata;

use Oro\Bundle\LayoutBundle\Exception\InvalidLayoutCacheMetadataException;

/**
 * Model to store and validate layout cache metadata.
 */
class LayoutCacheMetadata
{
    /**
     * @var string[]
     */
    private $varyBy = [];

    /**
     * @var int|null
     */
    private $maxAge;

    /**
     * @var array
     */
    private $tags = [];

    /**
     * @return string[]
     */
    public function getVaryBy(): array
    {
        return $this->varyBy;
    }

    /**
     * @param string[] $varyBy
     * @return $this
     */
    public function setVaryBy(array $varyBy): LayoutCacheMetadata
    {
        $this->assertArrayOfScalars($varyBy, 'varyBy');
        $this->varyBy = $varyBy;

        return $this;
    }

    public function getMaxAge(): ?int
    {
        return $this->maxAge;
    }

    /**
     * @param int|null $maxAge
     * @return $this
     */
    public function setMaxAge(?int $maxAge): LayoutCacheMetadata
    {
        $this->maxAge = $maxAge;

        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): LayoutCacheMetadata
    {
        $this->assertArrayOfScalars($tags, 'tags');
        $this->tags = $tags;

        return $this;
    }

    private function assertArrayOfScalars(array $values, string $optionName): void
    {
        foreach ($values as $key => $value) {
            if (!is_scalar($value)) {
                throw new InvalidLayoutCacheMetadataException(
                    sprintf(
                        'The value of the "cache.%s.%s" block option is expected to be a scalar but got "%s".',
                        $optionName,
                        $key,
                        \is_object($value) ? \get_class($value) : \gettype($value)
                    )
                );
            }
        }
    }
}
