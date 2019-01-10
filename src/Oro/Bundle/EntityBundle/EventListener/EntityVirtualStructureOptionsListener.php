<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Helper\UnidirectionalFieldHelper;
use Oro\Bundle\EntityBundle\Provider\ChainVirtualFieldProvider;

/**
 * Adds "virtual" option for virtual fields.
 */
class EntityVirtualStructureOptionsListener
{
    private const OPTION_NAME = 'virtual';

    /** @var ChainVirtualFieldProvider */
    private $virtualFieldProvider;

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
            $className = $entityStructure->getClassName();
            $fields = $entityStructure->getFields();
            foreach ($fields as $field) {
                $fieldName = $field->getName();
                if (UnidirectionalFieldHelper::isFieldUnidirectional($fieldName)) {
                    $realFieldName = UnidirectionalFieldHelper::getRealFieldName($fieldName);
                    $realFieldClass = UnidirectionalFieldHelper::getRealFieldClass($fieldName);
                } else {
                    $realFieldName = $fieldName;
                    $realFieldClass = $className;
                }
                if ($this->virtualFieldProvider->isVirtualField($realFieldClass, $realFieldName)) {
                    $field->addOption(self::OPTION_NAME, true);
                }
            }
        }
        $event->setData($data);
    }
}
