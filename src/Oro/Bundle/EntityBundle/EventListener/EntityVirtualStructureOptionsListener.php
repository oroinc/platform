<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityBundle\Provider\ChainVirtualFieldProvider;

class EntityVirtualStructureOptionsListener
{
    const OPTION_NAME = 'virtual';

    /** @var ChainVirtualFieldProvider */
    protected $virtualFieldProvider;

    /**
     * @param ChainVirtualFieldProvider $virtualFieldProvider
     */
    public function __construct(ChainVirtualFieldProvider $virtualFieldProvider)
    {
        $this->virtualFieldProvider = $virtualFieldProvider;
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

            $fields = $entityStructure->getFields();
            foreach ($fields as $field) {
                $isVirtualField = $this->virtualFieldProvider->isVirtualField($className, $field->getName());
                $field->addOption(self::OPTION_NAME, $isVirtualField);
            }
        }

        $event->setData($data);
    }
}
