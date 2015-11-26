<?php

namespace Oro\Bundle\TagBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Oro\Bundle\TagBundle\Entity\TagManager;

class TagListener implements ContainerAwareInterface
{
    /** @var TagManager */
    protected $tagManager;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        if ((null === $this->tagManager) && $this->container) {
            $this->tagManager = $this->container->get('oro_tag.tag.manager');
        }

        $entity = $args->getEntity();
        if ($this->tagManager->isTaggable($entity)) {
            $this->tagManager->deleteTagging($entity, []);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
