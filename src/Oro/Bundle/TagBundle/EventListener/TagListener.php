<?php

namespace Oro\Bundle\TagBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\TagBundle\Entity\Taggable;

/**
 * TagListener.
 */
class TagListener implements ContainerAwareInterface
{
    protected $manager;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        if ((null === $this->manager) && $this->container) {
            $this->manager = $this->container->get('oro_tag.tag.manager');
        }

        $entity = $args->getEntity();

        if ($this->manager->isTaggable($args->getEntity())) {
            $this->manager->deleteTagging($entity, []);
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
