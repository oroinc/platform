<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Event\AfterRemoveFieldEvent;

class AfterRemoveFieldListener
{
    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param AfterRemoveFieldEvent $event
     */
    public function onAfterRemove(AfterRemoveFieldEvent $event)
    {
        $field = $event->getFieldConfig();

        $this->doctrineHelper
            ->getEntityRepository(AttributeGroupRelation::class)
            ->removeByFieldId($field->getId());
    }
}
