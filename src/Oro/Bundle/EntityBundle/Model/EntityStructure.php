<?php

namespace Oro\Bundle\EntityBundle\Model;

class EntityStructure
{
    /**
     * This field required for JSON API
     *
     * @var string
     */
    protected $id;

    /** @var string */
    protected $label;

    /** @var string */
    protected $pluralLabel;

    /** @var string */
    protected $alias;

    /** @var string */
    protected $pluralAlias;

    /** @var string */
    protected $className;

    /** @var string */
    protected $icon;

    /** @var array|EntityFieldStructure[] */
    protected $fields = [];

    /** @var array */
    protected $options = [];

    /** @var array */
    protected $routes = [];

    /**
     * @param $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     *
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @return string
     */
    public function getPluralAlias()
    {
        return $this->pluralAlias;
    }

    /**
     * @param string $pluralAlias
     *
     * @return $this
     */
    public function setPluralAlias($pluralAlias)
    {
        $this->pluralAlias = $pluralAlias;

        return $this;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $className
     *
     * @return $this
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
    }

    /**
     * @return array|EntityFieldStructure[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param array|EntityFieldStructure[] $fields
     *
     * @return $this
     */
    public function setFields(array $fields)
    {
        $this->fields = [];

        foreach ($fields as $field) {
            $this->addField($field);
        }

        return $this;
    }

    /**
     * @param EntityFieldStructure $field
     *
     * @return $this
     */
    public function addField(EntityFieldStructure $field)
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function addOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getOption($name)
    {
        if (!$this->hasOption($name)) {
            return null;
        }

        return $this->options[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasOption($name)
    {
        return isset($this->options[$name]);
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
    public function getPluralLabel()
    {
        return $this->pluralLabel;
    }

    /**
     * @param string $pluralLabel
     *
     * @return $this
     */
    public function setPluralLabel($pluralLabel)
    {
        $this->pluralLabel = $pluralLabel;

        return $this;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     *
     * @return $this
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param array $routes
     *
     * @return $this
     */
    public function setRoutes($routes)
    {
        $this->routes = $routes;

        return $this;
    }
}
