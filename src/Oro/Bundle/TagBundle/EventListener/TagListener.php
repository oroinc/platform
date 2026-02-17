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
        $entity = $args->getObject();
        if ($this->getTaggableHelper()->isTaggable($entity)) {
            $this->getTagManager()->deleteTagging($entity, []);
        }
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            TaggableHelper::class,
            TagManager::class
        ];
    }

    private function getTaggableHelper(): TaggableHelper
    {
        if (null === $this->taggableHelper) {
            $this->taggableHelper = $this->container->get(TaggableHelper::class);
        }

        return $this->taggableHelper;
    }

    private function getTagManager(): TagManager
    {
        if (null === $this->tagManager) {
            $this->tagManager = $this->container->get(TagManager::class);
        }

        return $this->tagManager;
    }
}
