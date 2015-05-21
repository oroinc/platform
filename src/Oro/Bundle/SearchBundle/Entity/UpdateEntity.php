<?php

namespace Oro\Bundle\SearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_search_update")
 * @ORM\Entity
 */
class UpdateEntity
{
    /**
     * @var string
     *
     * @ORM\Column(name="entity", type="string")
     * @ORM\Id
     */
    protected $entity;

    /**
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param string $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }
}
