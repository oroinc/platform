<?php

namespace Oro\Bundle\TagBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Removes tags for deleted taggable entities.
 */
class TagListener implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private ?TaggableHelper $taggableHelper = null;
    private ?TagManager $tagManager = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();
        if ($this->getTaggableHelper()->isTaggable($entity)) {
            $this->getTagManager()->deleteTagging($entity, []);
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_tag.helper.taggable_helper' => TaggableHelper::class,
            'oro_tag.tag.manager' => TagManager::class
        ];
    }

    private function getTaggableHelper(): TaggableHelper
    {
        if (null === $this->taggableHelper) {
            $this->taggableHelper = $this->container->get('oro_tag.helper.taggable_helper');
        }

        return $this->taggableHelper;
    }

    private function getTagManager(): TagManager
    {
        if (null === $this->tagManager) {
            $this->tagManager = $this->container->get('oro_tag.tag.manager');
        }

        return $this->tagManager;
    }
}
