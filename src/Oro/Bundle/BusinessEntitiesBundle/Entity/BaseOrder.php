<?php

namespace Oro\Bundle\BusinessEntitiesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class BaseOrder
 *
 * @package Oro\Bundle\BusinessEntitiesBundle\Entity
 * @ORM\MappedSuperclass
 */
class BaseOrder
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
