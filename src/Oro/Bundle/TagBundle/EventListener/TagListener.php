<?php

namespace Oro\Bundle\TagBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TagListener implements ContainerAwareInterface
{
    /** @var TagManager */
    protected $tagManager;

    /** @var ContainerInterface */
    protected $container;

    /** @var TaggableHelper */
    protected $taggableHelper;

    /** @param TaggableHelper $helper */
    public function __construct(TaggableHelper $helper)
    {
        $this->taggableHelper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($this->taggableHelper->isTaggable($entity)) {
            if ((null === $this->tagManager) && $this->container) {
                $this->tagManager = $this->container->get('oro_tag.tag.manager');
            }
            $this->tagManager->deleteTagging($entity, []);
        }
    }
}
