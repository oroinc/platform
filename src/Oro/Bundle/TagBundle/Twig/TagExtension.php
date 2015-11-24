<?php

namespace Oro\Bundle\TagBundle\Twig;

use Oro\Bundle\TagBundle\Entity\TagManager;

class TagExtension extends \Twig_Extension
{
    /** @var TagManager */
    protected $tagManager;

    /** @param TagManager $tagManager */
    public function __construct(TagManager $tagManager)
    {
        $this->tagManager = $tagManager;
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
        return $this->tagManager->isTaggable($entity);
    }
}
