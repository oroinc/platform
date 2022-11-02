<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

/**
 * Adds entity alias and plural alias.
 */
class EntityAliasStructureOptionsListener
{
    /** @var EntityAliasResolver */
    private $entityAliasResolver;

    public function __construct(EntityAliasResolver $entityAliasResolver)
    {
        $this->entityAliasResolver = $entityAliasResolver;
    }

    public function onOptionsRequest(EntityStructureOptionsEvent $event)
    {
        $data = $event->getData();
        foreach ($data as $entityStructure) {
            $className = $entityStructure->getClassName();
            if (!$this->entityAliasResolver->hasAlias($className)) {
                continue;
            }

            $entityStructure->setAlias($this->entityAliasResolver->getAlias($className));
            $entityStructure->setPluralAlias($this->entityAliasResolver->getPluralAlias($className));
        }
        $event->setData($data);
    }
}
