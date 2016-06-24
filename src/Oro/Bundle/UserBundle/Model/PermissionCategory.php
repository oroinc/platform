<?php

namespace Oro\Bundle\UserBundle\Model;

class PermissionCategory
{
    /**
     * @var string
     */
    protected $id;
    
    /**
     * @var string
     */
    protected $label;

    /**
     * @var bool
     */
    protected $tab;

    /**
     * @var int
     */
    protected $priority;

    /**
     * @param string $id
     * @param string $label
     * @param bool $tab
     * @param int $priority
     */
    public function __construct($id, $label, $tab, $priority)
    {
        $this->id = $id;
        $this->label = $label;
        $this->tab = $tab;
        $this->priority = $priority;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return PermissionCategory
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return PermissionCategory
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return bool
     */
    public function getTab()
    {
        return $this->tab;
    }

    /**
     * @param bool $tab
     *
     * @return PermissionCategory
     */
    public function setTab($tab)
    {
        $this->tab = $tab;

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
     *
     * @return PermissionCategory
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }
}
