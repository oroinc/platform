<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener\Cache;

use Oro\Bundle\EntityExtendBundle\Entity\EnumValueTranslation;

/**
 * Listen to updates of EnumValueTranslation and invalidate a cache
 */
class EnumValueTranslationListener extends AbstractEnumValueListener
{
    /**
     * {@inheritdoc}
     */
    protected function invalidateCache($entity)
    {
        if ($entity instanceof EnumValueTranslation) {
            $this->enumTranslationCache->invalidate($entity->getObjectClass());
        }
    }
}
