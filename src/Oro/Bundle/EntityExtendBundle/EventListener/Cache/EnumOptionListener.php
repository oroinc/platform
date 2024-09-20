<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener\Cache;

use Oro\Bundle\EntityExtendBundle\Async\Topic\ActualizeEntityEnumOptionsTopic;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;

/**
 * Listen to updates of EnumOption and invalidate a cache
 */
class EnumOptionListener extends AbstractEnumOptionListener
{
    public function postRemove(object $entity): void
    {
        parent::postRemove($entity);
        $this->actualizedEntityEnumOption($entity);
    }

    public function postPersist(object $entity): void
    {
        parent::postPersist($entity);
        $this->setEntityToUpdateTranslation($entity);
    }

    protected function invalidateCache(object $entity): void
    {
        if ($entity instanceof EnumOptionInterface) {
            $this->enumTranslationCache->invalidate($entity->getEnumCode());
        }
    }

    protected function getEntityTranslationInfo(object $entity): array
    {
        return $entity instanceof EnumOptionInterface
            ? [$entity->getId(), $entity->getName(), $entity->getLocale()]
            : [null, null, null];
    }

    protected function actualizedEntityEnumOption(object $entity): void
    {
        if (!$entity instanceof EnumOptionInterface) {
            return;
        }
        $this->messageProducer->send(
            ActualizeEntityEnumOptionsTopic::getName(),
            [
                ActualizeEntityEnumOptionsTopic::ENUM_CODE => $entity->getEnumCode(),
                ActualizeEntityEnumOptionsTopic::ENUM_OPTION_ID => $entity->getId(),
            ]
        );
    }
}
