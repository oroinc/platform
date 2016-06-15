<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Fixture;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table
 */
class LoggableClass
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true)
     */
    protected $name;

    /**
     * @var LoggableCollectionClass[]
     *
     * @ORM\ManyToMany(targetEntity="LoggableCollectionClass")
     */
    protected $collection;

    /**
     * @var LoggableCollectionClass[]
     *
     * @ORM\ManyToMany(targetEntity="LoggableCollectionClass")
     */
    protected $collectionWithMethodName;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $collection
     * @return $this
     */
    public function setCollection($collection)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @param LoggableCollectionClass[] $collectionWithMethodName
     * @return $this
     */
    public function setCollectionWithMethodName($collectionWithMethodName)
    {
        $this->collectionWithMethodName = $collectionWithMethodName;

        return $this;
    }

    /**
     * @return LoggableCollectionClass[]
     */
    public function getCollectionWithMethodName()
    {
        return $this->collectionWithMethodName;
    }
}
