<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\ChainVirtualFieldProvider;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;

class EntityStructureOptionsListener
{
    const OPTION_NAME = 'virtual';

    /** @var EntityAliasProviderInterface */
    protected $entityAliasProvider;

    /** @var ChainVirtualFieldProvider */
    protected $virtualFieldProvider;

    /**
     * @param EntityAliasProviderInterface $entityAliasProvider
     * @param ChainVirtualFieldProvider $virtualFieldProvider
     */
    public function __construct(
        EntityAliasProviderInterface $entityAliasProvider,
        ChainVirtualFieldProvider $virtualFieldProvider
    ) {
        $this->entityAliasProvider = $entityAliasProvider;
        $this->virtualFieldProvider = $virtualFieldProvider;
    }

    /**
     * @param EntityStructureOptionsEvent $event
     */
    public function onOptionsRequest(EntityStructureOptionsEvent $event)
    {
        $data = $event->getData();

        foreach ($data as $entityStructure) {
            $className = $entityStructure->getClassName();

            $alias = $this->entityAliasProvider->getEntityAlias($className);
            if ($alias instanceof EntityAlias) {
                $entityStructure->setAlias($alias->getAlias())
                    ->setPluralAlias($alias->getPluralAlias());
            }

            $fields = $entityStructure->getFields();
            foreach ($fields as $field) {
                $isVirtualField = $this->virtualFieldProvider->isVirtualField($className, $field->getName());
                $field->addOption(self::OPTION_NAME, $isVirtualField);
            }
        }

        $event->setData($data);
    }
}
