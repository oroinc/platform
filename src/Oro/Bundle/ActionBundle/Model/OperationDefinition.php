<?php

namespace Oro\Bundle\ActionBundle\Model;

class OperationDefinition
{
    const PREACTIONS = 'preactions';
    const FORM_INIT = 'form_init';
    const ACTIONS = 'actions';

    const PRECONDITIONS = 'preconditions';
    const CONDITIONS = 'conditions';

    /** @var string */
    private $name;

    /** @var string */
    private $label;

    /** @var boolean */
    private $enabled = true;

    /** @var boolean */
    private $pageReload = true;

    /** @var string */
    private $substituteOperation;

    /** @var integer */
    private $order = 0;

    /** @var array */
    private $buttonOptions = [];

    /** @var array */
    private $frontendOptions = [];

    /** @var array */
    private $datagridOptions = [];

    /** @var string */
    private $formType;

    /** @var array */
    private $formOptions = [];

    /** @var array */
    private $attributes = [];

    /** @var array */
    private $actions = [];

    /** @var array */
    private $conditions = [];

    /** @var array */
    private $actionGroups = [];

    /**
     * @return array
     */
    public static function getAllowedActions()
    {
        return [self::PREACTIONS, self::FORM_INIT, self::ACTIONS];
    }

    /**
     * @return array
     */
    public static function getAllowedConditions()
    {
        return [self::PRECONDITIONS, self::CONDITIONS];
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

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
     * @param boolean $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return bool
     */
    public function isPageReload(): bool
    {
        return $this->pageReload;
    }

    /**
     * @param bool $pageReload
     * @return $this
     */
    public function setPageReload(bool $pageReload)
    {
        $this->pageReload = (bool)$pageReload;

        return $this;
    }

    /**
     * @param integer $order
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return integer
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return array
     */
    public function getButtonOptions()
    {
        return $this->buttonOptions;
    }

    /**
     * @param array $buttonOptions
     * @return $this
     */
    public function setButtonOptions(array $buttonOptions)
    {
        $this->buttonOptions = $buttonOptions;

        return $this;
    }

    /**
     * @return array
     */
    public function getDatagridOptions()
    {
        return $this->datagridOptions;
    }

    /**
     * @param array $datagridOptions
     * @return $this
     */
    public function setDatagridOptions(array $datagridOptions)
    {
        $this->datagridOptions = $datagridOptions;

        return $this;
    }

    /**
     * @return array
     */
    public function getFrontendOptions()
    {
        return $this->frontendOptions;
    }

    /**
     * @param array $frontendOptions
     * @return $this
     */
    public function setFrontendOptions(array $frontendOptions)
    {
        $this->frontendOptions = $frontendOptions;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormType()
    {
        return $this->formType;
    }

    /**
     * @param string $formType
     * @return $this
     */
    public function setFormType($formType)
    {
        $this->formType = $formType;

        return $this;
    }

    /**
     * @return array
     */
    public function getFormOptions()
    {
        return $this->formOptions;
    }

    /**
     * @param array $formOptions
     * @return $this
     */
    public function setFormOptions(array $formOptions)
    {
        $this->formOptions = $formOptions;

        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @param string $name
     * @return array
     */
    public function getActions($name = null)
    {
        if ($name === null) {
            return $this->actions;
        }

        return isset($this->actions[$name]) ? $this->actions[$name] : [];
    }

    /**
     * @param string $name
     * @param array $data
     * @return $this
     */
    public function setActions($name, array $data)
    {
        $this->actions[$name] = $data;

        return $this;
    }

    /**
     * @param string $name
     * @return array
     */
    public function getConditions($name = null)
    {
        if ($name === null) {
            return $this->conditions;
        }

        return isset($this->conditions[$name]) ? $this->conditions[$name] : [];
    }

    /**
     * @param string $name
     * @param array $data
     * @return $this
     */
    public function setConditions($name, array $data)
    {
        $this->conditions[$name] = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getActionGroups()
    {
        return $this->actionGroups;
    }

    /**
     * @param array $actionGroups
     * @return $this
     */
    public function setActionGroups(array $actionGroups)
    {
        $this->actionGroups = $actionGroups;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubstituteOperation()
    {
        return $this->substituteOperation;
    }

    /**
     * @param string $substituteOperation
     * @return $this
     */
    public function setSubstituteOperation($substituteOperation)
    {
        $this->substituteOperation = $substituteOperation;

        return $this;
    }
}
