<?php

namespace Oro\Bundle\ActivityListBundle\Model\Accessor;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\DefaultAccessor;

/**
 * The entity data accessor for activity entities.
 */
class ActivityAccessor extends DefaultAccessor
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'activity';
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($entity, FieldMetadata $metadata)
    {
        if ($metadata->has('activity') && $metadata->has('type') && $metadata->get('activity') === true) {
            return $this->getRepository()->getRecordsCountForTargetClassAndId(
                ClassUtils::getClass($entity),
                $entity->getId(),
                [$metadata->get('type')]
            );
        }

        return parent::getValue($entity, $metadata);
    }

    private function getRepository(): ActivityListRepository
    {
        return $this->doctrine->getManagerForClass(ActivityList::class)
            ->getRepository(ActivityList::class);
    }
}
