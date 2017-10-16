<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;

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
            $className = $entityStructure->getClassName();
            $isAuditableEntity = $this->auditConfigProvider->isAuditableEntity($className);
            $entityStructure->addOption(self::OPTION_NAME, $isAuditableEntity);
            $fields = $entityStructure->getFields();
            foreach ($fields as $field) {
                $isAuditableField = $this->auditConfigProvider->isAuditableField(
                    $className,
                    $field->getName()
                );
                $field->addOption(self::OPTION_NAME, $isAuditableField);
            }
        }
        $event->setData($data);
    }
}
