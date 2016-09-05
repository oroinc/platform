<?php

namespace Oro\Bundle\NavigationBundle\Model;

use Doctrine\ORM\Mapping as ORM;

abstract class AbstractMenuUpdate
{
    const OWNERSHIP_GLOBAL        = 1;
    const OWNERSHIP_ORGANIZATION  = 2;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="key", type="string", length=100)
     */
    protected $key;

    /**
     * @var string
     *
     * @ORM\Column(name="parent_key", type="string", length=100)
     */
    protected $parentKey;

    /**
     * @var string
     *
     * @ORM\Column(name="uri", type="string", length=255, nullable=true)
     */
    protected $uri;

    /**
     * @var string
     *
     * @ORM\Column(name="menu", type="string", length=100)
     */
    protected $menu;

    /**
     * @var int
     *
     * @ORM\Column(name="ownership_type", type="integer")
     */
    protected $ownershipType;

    /**
     * @var int
     *
     * @ORM\Column(name="owner_id", type="integer", nullable=true)
     */
    protected $ownerId;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    protected $active;

    /**
     * @var int
     *
     * @ORM\Column(name="priority", type="integer", nullable=true)
     */
    protected $priority;

    /**
     * Get array of extra data that is not declared in AbstractMenuUpdate model
     * @return array
     */
    abstract public function getExtras();

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return AbstractMenuUpdate
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return AbstractMenuUpdate
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getParentKey()
    {
        return $this->parentKey;
    }

    /**
     * @param string $parentKey
     * @return AbstractMenuUpdate
     */
    public function setParentKey($parentKey)
    {
        $this->parentKey = $parentKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     * @return AbstractMenuUpdate
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * @return string
     */
    public function getMenu()
    {
        return $this->menu;
    }

    /**
     * @param string $menu
     * @return AbstractMenuUpdate
     */
    public function setMenu($menu)
    {
        $this->menu = $menu;

        return $this;
    }

    /**
     * @return int
     */
    public function getOwnershipType()
    {
        return $this->ownershipType;
    }

    /**
     * @param int $ownershipType
     * @return AbstractMenuUpdate
     */
    public function setOwnershipType($ownershipType)
    {
        $this->ownershipType = $ownershipType;

        return $this;
    }

    /**
     * @return int
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /**
     * @param int $ownerId
     * @return AbstractMenuUpdate
     */
    public function setOwnerId($ownerId)
    {
        $this->ownerId = $ownerId;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     * @return AbstractMenuUpdate
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     * @return AbstractMenuUpdate
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }
}
