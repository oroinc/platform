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
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return TagManager
     */
    protected function getTagManager()
    {
        return $this->container->get('oro_tag.tag.manager');
    }

    /**
     * @return TaggableHelper
     */
    protected function getTaggableHelper()
    {
        return $this->container->get('oro_tag.helper.taggable_helper');
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_tag.tag.manager' => TagManager::class,
            'oro_tag.helper.taggable_helper' => TaggableHelper::class,
        ];
    }
}
