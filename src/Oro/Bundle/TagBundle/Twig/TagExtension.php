<?php

namespace Oro\Bundle\TagBundle\Twig;

use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to render entity tags:
 *   - oro_tag_get_list
 *   - oro_is_taggable
 */
class TagExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_tag_get_list', [$this, 'getList']),
            new TwigFunction('oro_is_taggable', [$this, 'isTaggable']),
        ];
    }

    /**
     * Return array of tags
     *
     * @param object $entity
     *
     * @return array
     */
    public function getList($entity)
    {
        return $this->getTagManager()->getPreparedArray($entity);
    }

    /**
     * @param  object $entity
     *
     * @return bool
     */
    public function isTaggable($entity)
    {
        return $this->getTaggableHelper()->isTaggable($entity);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            TagManager::class,
            TaggableHelper::class
        ];
    }

    private function getTagManager(): TagManager
    {
        return $this->container->get(TagManager::class);
    }

    private function getTaggableHelper(): TaggableHelper
    {
        return $this->container->get(TaggableHelper::class);
    }
}
