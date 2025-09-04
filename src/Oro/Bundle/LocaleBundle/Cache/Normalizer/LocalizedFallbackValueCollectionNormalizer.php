<?php

namespace Oro\Bundle\LocaleBundle\Cache\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

/**
 * Normalizes a collection of localized fallback value entities for usage in cache.
 */
class LocalizedFallbackValueCollectionNormalizer
{
    public function __construct(
        private readonly LocalizedFallbackValueNormalizer $localizedFallbackValueNormalizer
    ) {
    }

    public function normalize(iterable $localizedFallbackValues): array
    {
        $normalizedData = [];
        /** @var AbstractLocalizedFallbackValue $val */
        foreach ($localizedFallbackValues as $val) {
            $normalizedData[] = $this->localizedFallbackValueNormalizer->normalize($val);
        }

        return $normalizedData;
    }

    public function denormalize(array $normalizedData, string $entityClass): Collection
    {
        $collection = [];
        foreach ($normalizedData as $item) {
            $collection[] = $this->localizedFallbackValueNormalizer->denormalize($item, $entityClass);
        }

        return new ArrayCollection($collection);
    }
}
