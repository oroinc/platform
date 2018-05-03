<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener\Cache;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

/**
 * Listen to updates of EnumValue and invalidate a cache
 */
class EnumValueListener extends AbstractEnumValueListener
{
    /**
     * {@inheritdoc}
     */
    protected function invalidateCache($entity)
    {
        if ($entity instanceof AbstractEnumValue) {
            $this->enumTranslationCache->invalidate(ClassUtils::getClass($entity));
        }
    }
}
