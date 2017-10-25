<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
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

            $entityStructure->addOption(
                self::OPTION_NAME,
                $this->auditConfigProvider->isAuditableEntity($className)
            );

            $fields = $entityStructure->getFields();
            foreach ($fields as $field) {
                $field->addOption(
                    self::OPTION_NAME,
                    $this->auditConfigProvider->isAuditableField($className, $field->getName())
                );
            }
        }

        $event->setData($data);
    }
}
