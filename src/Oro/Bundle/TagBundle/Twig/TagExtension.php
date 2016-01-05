<?php

namespace Oro\Bundle\TagBundle\Twig;

use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;

class TagExtension extends \Twig_Extension
{
    /** @var TagManager */
    protected $tagManager;

    /** @var TaggableHelper */
    protected $taggableHelper;

    /**
     * @param TagManager     $tagManager
     * @param TaggableHelper $helper
     */
    public function __construct(TagManager $tagManager, TaggableHelper $helper)
    {
        $this->tagManager     = $tagManager;
        $this->taggableHelper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            'oro_tag_get_list' => new \Twig_Function_Method($this, 'getList'),
            'oro_is_taggable'  => new \Twig_Function_Method($this, 'isTaggable'),
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
        return $this->tagManager->getPreparedArray($entity);
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
        return $this->taggableHelper->isTaggable($entity);
    }
}
