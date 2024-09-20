<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener\Cache;

use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionTranslation;

/**
 * Listen to updates of EnumOptionTranslation and invalidate a cache
 */
class EnumOptionTranslationListener extends AbstractEnumOptionListener
{
    protected function invalidateCache($entity): void
    {
        if ($entity instanceof EnumOptionTranslation) {
            $enumOption = $this->doctrine->getManagerForClass(EnumOption::class)
                ->getRepository(EnumOption::class)
                ->find($entity->getForeignKey());
            if (null !== $enumOption) {
                $this->enumTranslationCache->invalidate($enumOption->getEnumCode());
            }
        }
    }

    protected function getEntityTranslationInfo(object $entity): array
    {
        return $entity instanceof EnumOptionTranslation
            ? [$entity->getForeignKey(), $entity->getContent(), $entity->getLocale()]
            : [null, null, null];
    }
}
