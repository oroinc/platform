<?php

namespace Oro\Bundle\SegmentBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class DoctrinePreRemoveListener
{
    /** @var ConfigManager */
    protected $cm;

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
        $em     = $args->getEntityManager();

        if ($this->cm->hasConfig(ClassUtils::getClass($entity))) {
            $em->getRepository('OroSegmentBundle:SegmentSnapshot')->removeByEntity($entity);
        }
    }
}
