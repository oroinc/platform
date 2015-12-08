<?php

namespace Oro\Bundle\ActivityListBundle\Model\Accessor;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\DefaultAccessor;

class ActivityAccessor extends DefaultAccessor
{
    /** @var Registry */
    protected $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
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
            return $this->getActivity($entity, $metadata->get('type'));
        }

        return parent::getValue($entity, $metadata);
    }

    /**
     * @param object $entity
     * @param string $type
     *
     * @return string
     */
    protected function getActivity($entity, $type)
    {
        $em = $this->registry->getManagerForClass(ActivityList::ENTITY_NAME);
        /** @var ActivityListRepository $repository */
        $repository = $em->getRepository(ActivityList::ENTITY_NAME);
        $count = $repository->getRecordsCountForTargetClassAndId(
            ClassUtils::getClass($entity),
            $entity->getId(),
            [$type]
        );

        return $count;
    }
}
