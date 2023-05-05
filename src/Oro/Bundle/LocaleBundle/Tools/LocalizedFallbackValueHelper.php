<?php

namespace Oro\Bundle\LocaleBundle\Tools;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

/**
 * Contains handy method for working with localized fallback values.
 */
class LocalizedFallbackValueHelper
{
    /**
     * @param Collection<AbstractLocalizedFallbackValue> $collection
     *
     * @return Collection<AbstractLocalizedFallbackValue>
     */
    public static function cloneCollection(Collection $collection, ?string $entityClass = null): Collection
    {
        $first = $collection->first();
        if (!$first) {
            return new ArrayCollection();
        }

        if ($entityClass !== null && !is_a($entityClass, AbstractLocalizedFallbackValue::class, true)) {
            throw new \LogicException(
                sprintf(
                    'The argument "$entityClass" is expected to be a heir of "%s"',
                    AbstractLocalizedFallbackValue::class
                )
            );
        }

        $values = [];
        foreach ($collection as $value) {
            if ($entityClass !== null) {
                $values[] = $entityClass::createFromAbstract($value);
            } else {
                $values[] = clone $value;
            }
        }

        return new ArrayCollection($values);
    }
}
