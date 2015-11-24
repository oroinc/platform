<?php

namespace Oro\Bundle\TagBundle\Twig;

use Oro\Bundle\TagBundle\Entity\TagManager;

class TagExtension extends \Twig_Extension
{
    /** @var TagManager */
    protected $manager;

    public function __construct(TagManager $manager)
    {
        $this->manager = $manager;
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
        return $this->manager->getPreparedArray($entity);
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
        return $this->manager->isTaggable($entity);
    }
}
