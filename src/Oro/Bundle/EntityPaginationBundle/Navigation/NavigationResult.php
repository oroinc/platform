<?php

namespace Oro\Bundle\EntityPaginationBundle\Navigation;

class NavigationResult
{
    /** @var int|null  */
    protected $id = null;

    /** @var bool  */
    protected $available = true;

    /** @var bool  */
    protected $accessible = true;

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param boolean $accessible
     */
    public function setAccessible($accessible)
    {
        $this->accessible = $accessible;
    }

    /**
     * @param boolean $available
     */
    public function setAvailable($available)
    {
        $this->available = $available;
    }

    /**
     * @return boolean
     */
    public function isAccessible()
    {
        return $this->accessible;
    }

    /**
     * @return boolean
     */
    public function isAvailable()
    {
        return $this->available;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}
