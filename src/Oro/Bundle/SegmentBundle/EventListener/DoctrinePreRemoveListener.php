<?php

namespace Oro\Bundle\SegmentBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentSnapshotRepository;

class DoctrinePreRemoveListener
{
    /** @var array */
    protected $deleteEntities;

    /** @var ConfigManager */
    private $cm;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param ConfigManager  $cm
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ConfigManager $cm, DoctrineHelper $doctrineHelper)
    {
        $this->cm = $cm;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Remove references from snapshot table
     *
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $className = ClassUtils::getClass($entity);

        if ($this->cm->hasConfig($className)) {
            $metadata  = $this->doctrineHelper->getEntityMetadata($className);
            $entityIds = $metadata->getIdentifierValues($entity);
            $this->deleteEntities[] = [
                'id'     => reset($entityIds),
                'entity' => $entity
            ];
        }
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if ($this->deleteEntities) {
            /** @var SegmentSnapshotRepository $repository */
            $repository = $this->doctrineHelper->getEntityRepository('OroSegmentBundle:SegmentSnapshot');
            $repository->massRemoveByEntities($this->deleteEntities);
            $this->deleteEntities = [];
        }
    }
}
