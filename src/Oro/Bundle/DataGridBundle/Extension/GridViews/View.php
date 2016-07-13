<?php

namespace Oro\Bundle\DataGridBundle\Extension\GridViews;

class View implements ViewInterface
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

    /** @var string */
    protected $gridName;

    /** @var bool */
    protected $editable = false;

    /** @var bool */
    protected $deletable = false;

    /** @var bool */
    protected $default = false;

    /** @var string|null */
    protected $sharedBy;

    /**
     * @var array
     *
     * Format array:
     * [columnName => ['order' => int, 'renderable' => bool]]
     *    columnName: column name
     *    order: column number in grid
     *    renderable: visible in grid or not
     */
    protected $columnsData;

    /**
     * @param string $name
     * @param array  $filtersData
     * @param array  $sortersData
     * @param string $type
     * @param array  $columnsData
     */
    public function __construct(
        $name,
        array $filtersData = [],
        array $sortersData = [],
        $type = 'system',
        array $columnsData = []
    ) {
        $this->name        = $name;
        $this->label       = $name;
        $this->filtersData = $filtersData;
        $this->sortersData = $sortersData;
        $this->type        = $type;
        $this->columnsData = $columnsData;
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
     * Getter for label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
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
     * {@inheritdoc}
     */
    public function setSortersData(array $sortersData = [])
    {
        $this->sortersData = $sortersData;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortersData()
    {
        return $this->sortersData;
    }

    /**
     * {@inheritdoc}
     */
    public function setFiltersData(array $filtersData)
    {
        $this->filtersData = $filtersData;

        return $this;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getColumnsData()
    {
        if ($this->columnsData === null) {
            $this->columnsData = [];
        }

        return $this->columnsData;
    }

    /**
     * {@inheritdoc}
     */
    public function setColumnsData(array $columnsData = [])
    {
        $this->columnsData = $columnsData;
    }

    /**
     * @return boolean
     */
    public function isDefault()
    {
        return $this->default;
    }

    /**
     * @param boolean $default
     *
     * @return $this
     */
    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getSharedBy()
    {
        return $this->sharedBy;
    }

    /**
     * @param null|string $sharedBy
     */
    public function setSharedBy($sharedBy)
    {
        $this->sharedBy = $sharedBy;
    }

    /**
     * {@inheritdoc}
     */
    public function setGridName($gridName)
    {
        $this->gridName = $gridName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getGridName()
    {
        return $this->gridName;
    }

    /**
     * Convert to view data
     *
     * @return array
     */
    public function getMetadata()
    {
        return [
            'name'       => $this->getName(),
            'label'      => $this->label,
            'type'       => $this->getType(),
            'filters'    => $this->getFiltersData(),
            'sorters'    => $this->getSortersData(),
            'columns'    => $this->columnsData,
            'editable'   => $this->editable,
            'deletable'  => $this->deletable,
            'is_default' => $this->default,
            'shared_by'  => $this->sharedBy
        ];
    }
}
