<?php

namespace Oro\Bundle\AttachmentBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;

class AttachmentListener
{
    /**
     * @var AttachmentManager
     */
    protected $manager;

    /**
     * @param AttachmentManager $manager
     */
    public function __construct(AttachmentManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        /** @var $entity Attachment */
        $entity = $args->getEntity();
        if ($entity instanceof Attachment) {
            $this->manager->preUpload($entity);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->prePersist($args);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        /** @var $entity Attachment */
        $entity = $args->getEntity();
        if ($entity instanceof Attachment) {
            $this->manager->upload($entity);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->postPersist($args);
    }
}
