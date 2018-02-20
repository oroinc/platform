<?php

namespace Oro\Bundle\TagBundle\Twig;

use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TagExtension extends \Twig_Extension
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
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
            new \Twig_SimpleFunction('oro_tag_get_list', [$this, 'getList']),
            new \Twig_SimpleFunction('oro_is_taggable', [$this, 'isTaggable']),
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_tag';
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
}
