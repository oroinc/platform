<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

class EntityAliasStructureOptionsListener
{
    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /**
     * @param EntityAliasResolver $entityAliasResolver
     */
    public function __construct(EntityAliasResolver $entityAliasResolver)
    {
        $this->entityAliasResolver = $entityAliasResolver;
    }

    /**
     * @param EntityStructureOptionsEvent $event
     */
    public function onOptionsRequest(EntityStructureOptionsEvent $event)
    {
        $data = $event->getData();

        foreach ($data as $entityStructure) {
            if (!$entityStructure instanceof EntityStructure) {
                continue;
            }

            $className = $entityStructure->getClassName();
            if (!$this->entityAliasResolver->hasAlias($className)) {
                continue;
            }

            $entityStructure
                ->setAlias($this->entityAliasResolver->getAlias($className))
                ->setPluralAlias($this->entityAliasResolver->getPluralAlias($className));
        }

        $event->setData($data);
    }
}
