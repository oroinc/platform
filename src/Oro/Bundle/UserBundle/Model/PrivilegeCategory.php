<?php

namespace Oro\Bundle\UserBundle\Model;

class PrivilegeCategory
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
     * @var bool
     */
    protected $visible;

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
        $this->visible = true;
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
     * @return PrivilegeCategory
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
     * @return PrivilegeCategory
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTab()
    {
        return $this->tab;
    }

    /**
     * @param bool $tab
     *
     * @return PrivilegeCategory
     */
    public function setTab($tab)
    {
        $this->tab = $tab;

        return $this;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * @param bool $visible
     *
     * @return PrivilegeCategory
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

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
     * @return PrivilegeCategory
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }
}
