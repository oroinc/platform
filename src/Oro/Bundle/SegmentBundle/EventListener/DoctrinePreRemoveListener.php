<?php

namespace Oro\Bundle\SegmentBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;

/**
 * Remove records from segment snapshot when referenced entity removed.
 */
class DoctrinePreRemoveListener
{
    /** @var ConfigManager */
    protected $cm;

    /** @var array */
    protected $deleteEntities = [];

    /**
     * @param ConfigManager $cm
     */
    public function __construct(ConfigManager $cm)
    {
        $this->cm = $cm;
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
            $metadata  = $args->getEntityManager()->getClassMetadata($className);
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
            $em = $args->getEntityManager();
            $knownNamespaces = $em->getConfiguration()->getEntityNamespaces();
            if (!empty($knownNamespaces['OroSegmentBundle'])) {
                $em->getRepository(SegmentSnapshot::class)->massRemoveByEntities($this->deleteEntities);
                $this->deleteEntities = [];
            }
        }
    }
}
