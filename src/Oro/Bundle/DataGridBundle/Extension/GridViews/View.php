<?php

namespace Oro\Bundle\DataGridBundle\Extension\GridViews;

class View
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $label;

    /** @var array */
    protected $filtersData;

    /** @var array */
    protected $sortersData;

    /** @var string */
    protected $type = 'system';

    /** @var bool */
    protected $editable = false;

    /** @var bool */
    protected $deletable = false;

    /**
     * @param string $name
     * @param array $filtersData
     * @param array $sortersData
     * @param string $type
     */
    public function __construct($name, array $filtersData = [], array $sortersData = [], $type = 'system')
    {
        $this->name        = $name;
        $this->label       = $name;
        $this->filtersData = $filtersData;
        $this->sortersData = $sortersData;
        $this->type        = $type;
    }

    /**
     * @param string $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Getter for name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Setter for sorters data
     *
     * @param array $sortersData
     *
     * @return $this
     */
    public function setSortersData(array $sortersData)
    {
        $this->sortersData = $sortersData;

        return $this;
    }

    /**
     * Getter for sorters data
     *
     * @return array
     */
    public function getSortersData()
    {
        return $this->sortersData;
    }

    /**
     * Setter for filter data
     *
     * @param array $filtersData
     *
     * @return $this
     */
    public function setFiltersData(array $filtersData)
    {
        $this->filtersData = $filtersData;

        return $this;
    }

    /**
     * Getter for filter data
     *
     * @return array
     */
    public function getFiltersData()
    {
        return $this->filtersData;
    }

    /**
     * Sets view as editable
     *
     * @param bool $editable
     *
     * @return $this
     */
    public function setEditable($editable = true)
    {
        $this->editable = $editable;

        return $this;
    }

    /**
     * Sets view as deletable
     *
     * @param bool $deletable
     *
     * @return $this
     */
    public function setDeletable($deletable = true)
    {
        $this->deletable = $deletable;

        return $this;
    }

    /**
     * Convert to view data
     *
     * @return array
     */
    public function getMetadata()
    {
        return [
            'name'      => $this->getName(),
            'label'     => $this->label,
            'type'      => $this->getType(),
            'filters'   => $this->getFiltersData(),
            'sorters'   => $this->getSortersData(),
            'editable'  => $this->editable,
            'deletable' => $this->deletable,
        ];
    }
}
