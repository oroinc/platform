<?php

namespace Oro\Bundle\TagBundle\Entity;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class TagEntityApiManager extends ApiEntityManager
{
    /** @var TagManager */
    protected $tagManager;

    /**
     * @param TagManager    $tagManager
     * @param string        $class Entity name
     * @param ObjectManager $om    Object manager
     */
    public function __construct(TagManager $tagManager, $class, ObjectManager $om)
    {
        parent::__construct($class, $om);
        $this->tagManager = $tagManager;
    }

    /**
     * Create new tag or load new one
     *
     * @return Tag
     */
    public function createEntity()
    {
        $name = func_get_arg(0);

        return $this->tagManager->loadOrCreateTag($name);
    }
}
