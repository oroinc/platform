<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRelationRepository;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;

/**
 * Removes the attribute group relation assigned to removed attribute.
 */
class RemoveAttributeGroupRelationListener
{
    /** @var ManagerRegistry */
    private $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param PostFlushConfigEvent $event
     */
    public function onPostFlushConfig(PostFlushConfigEvent $event)
    {
        $configManager = $event->getConfigManager();
        foreach ($event->getModels() as $model) {
            if (!$model instanceof FieldConfigModel) {
                continue;
            }

            $changeSet = $configManager->getFieldConfigChangeSet(
                'extend',
                $model->getEntity()->getClassName(),
                $model->getFieldName()
            );
            if ($changeSet && !empty($changeSet['is_deleted']) && $changeSet['is_deleted'][1]) {
                $values = $model->toArray('attribute');
                if (array_key_exists('is_attribute', $values) && $values['is_attribute']) {
                    $this->getAttributeGroupRelationRepository()
                        ->removeByFieldId($model->getId());
                }
            }
        }
    }

    /**
     * @return AttributeGroupRelationRepository
     */
    private function getAttributeGroupRelationRepository()
    {
        return $this->doctrine->getRepository(AttributeGroupRelation::class);
    }
}
