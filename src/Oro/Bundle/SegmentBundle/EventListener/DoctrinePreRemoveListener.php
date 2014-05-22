<?php

namespace Oro\Bundle\SegmentBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class DoctrinePreRemoveListener
{
    /** @var ConfigManager */
    protected $cm;

    /** @var array */
    protected $deleteEntities;

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

        if ($this->cm->hasConfig(ClassUtils::getClass($entity))) {
            $this->deleteEntities[] = $entity;
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
        }
    }
}
