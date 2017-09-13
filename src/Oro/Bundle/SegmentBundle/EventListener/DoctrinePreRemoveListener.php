<?php

namespace Oro\Bundle\SegmentBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class DoctrinePreRemoveListener
{
    /** @var ConfigManager */
    protected $cm;

    /** @var array */
    protected $deleteEntities;

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
            $em->getRepository('OroSegmentBundle:SegmentSnapshot')->massRemoveByEntities($this->deleteEntities);
            $this->deleteEntities = [];
        }
    }

    /**
     * @param DoctrineHelper $doctrineHelper
     * @deprecated This method is deprecated and will be removed. Inject Entity Manager via listeners
     * @see PostFlushEventArgs
     * @see LifecycleEventArgs
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        //This method is not needed anymore, since we should get Entity Manager from events.
    }
}
