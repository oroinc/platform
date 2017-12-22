<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Helper\UnidirectionalFieldHelper;
use Oro\Bundle\EntityBundle\Model\EntityStructure;

class EntityStructureOptionsListener
{
    const OPTION_NAME = 'auditable';

    /** @var AuditConfigProvider */
    protected $auditConfigProvider;

    /**
     * @param AuditConfigProvider $auditConfigProvider
     */
    public function __construct(AuditConfigProvider $auditConfigProvider)
    {
        $this->auditConfigProvider = $auditConfigProvider;
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

            if ($this->auditConfigProvider->isAuditableEntity($className)) {
                $entityStructure->addOption(self::OPTION_NAME, true);
            }

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
                if ($this->auditConfigProvider->isAuditableField($realFieldClass, $realFieldName)) {
                    $field->addOption(self::OPTION_NAME, true);
                }
            }
        }

        $event->setData($data);
    }
}
