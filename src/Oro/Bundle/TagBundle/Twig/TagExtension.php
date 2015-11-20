<?php

namespace Oro\Bundle\TagBundle\Twig;

use Symfony\Component\Routing\Router;

use Oro\Bundle\TagBundle\Entity\Taggable;
use Oro\Bundle\TagBundle\Entity\TagManager;

class TagExtension extends \Twig_Extension
{
    /**
     * @var \Oro\Bundle\TagBundle\Entity\TagManager
     */
    protected $manager;

    public function __construct(TagManager $manager)
    {
        $this->manager    = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            'oro_tag_get_list' => new \Twig_Function_Method($this, 'get'),
            'is_entity_taggable'=> new \Twig_Function_Method($this, 'isEntityTaggable'),
        ];
    }

    /**
     * Return array of tags
     *
     * @param  object $entity
     * @return array
     */
    public function get($entity)
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
    public function isEntityTaggable($entity)
    {
        return $this->manager->isTaggable($entity);
    }
}
