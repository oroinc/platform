<?php

namespace Oro\Component\Duplicator\Tests\Unit\Stub;

use Doctrine\Common\Collections\ArrayCollection;

class Entity1
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var Entity2
     */
    protected $entity;

    /**
     * @var ArrayCollection
     */
    protected $items;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var bool
     */
    protected $bool = true;

    /**
     * @var string
     */
    protected $string = 'some string';

    /**
     * @param int $id
     */
    public function __construct($id)
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return Entity2
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param Entity2 $entity
     */
    public function setEntity(Entity2 $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return ArrayCollection|EntityItem1[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param EntityItem1 $item
     */
    public function addItem(EntityItem1 $item)
    {
        $this->items->add($item);
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return bool
     */
    public function getBool()
    {
        return $this->bool;
    }

    /**
     * @return string
     */
    public function getString()
    {
        return $this->string;
    }
}
