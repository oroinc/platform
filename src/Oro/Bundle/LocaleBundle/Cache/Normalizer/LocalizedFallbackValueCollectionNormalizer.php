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
    private LocalizedFallbackValueNormalizer $localizedFallbackValueNormalizer;

    public function __construct(LocalizedFallbackValueNormalizer $localizedFallbackValueNormalizer)
    {
        $this->localizedFallbackValueNormalizer = $localizedFallbackValueNormalizer;
    }

    /**
     * @param iterable<AbstractLocalizedFallbackValue> $localizedFallbackValues
     * @return array
     *  [
     *      [
     *          'string' => ?string,
     *          'fallback' => ?string,
     *          'localization' => ?array [
     *              'id' => int
     *          ],
     *          // ...
     *      ],
     *      // ...
     *  ]
     */
    public function normalize(iterable $localizedFallbackValues): array
    {
        $normalizedData = [];
        foreach ($localizedFallbackValues as $localizedFallbackValue) {
            $normalizedData[] = $this->localizedFallbackValueNormalizer->normalize($localizedFallbackValue);
        }

        return $normalizedData;
    }

    /**
     * @param array $normalizedData
     *  [
     *      [
     *          'string' => ?string,
     *          'fallback' => ?string,
     *          'localization' => ?array [
     *              'id' => int
     *          ],
     *          // ...
     *      ],
     *      // ...
     *  ]
     * @param string $entityClass
     *
     * @return Collection
     */
    public function denormalize(array $normalizedData, string $entityClass): Collection
    {
        $collection = [];
        foreach ($normalizedData as $normalizedDatum) {
            $collection[] = $this->localizedFallbackValueNormalizer->denormalize($normalizedDatum, $entityClass);
        }

        return new ArrayCollection($collection);
    }
}
