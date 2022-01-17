<?php

namespace Oro\Bundle\EntityBundle\Model;

/**
 * Represents detailed information about an entity type.
 */
class EntityStructure
{
    /** @var string */
    private $id;

    /** @var string */
    private $label;

    /** @var string */
    private $pluralLabel;

    /** @var string */
    private $alias;

    /** @var string */
    private $pluralAlias;

    /** @var string */
    private $className;

    /** @var string */
    private $icon;

    /** @var EntityFieldStructure[] */
    private $fields = [];

    /** @var array */
    private $options = [];

    /** @var array */
    private $routes = [];

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
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
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
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
     */
    public function setPluralAlias($pluralAlias)
    {
        $this->pluralAlias = $pluralAlias;
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
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * @return EntityFieldStructure[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param EntityFieldStructure[] $fields
     */
    public function setFields(array $fields)
    {
        $this->fields = [];
        foreach ($fields as $field) {
            $this->addField($field);
        }
    }

    public function addField(EntityFieldStructure $field)
    {
        $this->fields[] = $field;
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
     * @param mixed  $value
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
     */
    public function setLabel($label)
    {
        $this->label = $label;
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
     */
    public function setPluralLabel($pluralLabel)
    {
        $this->pluralLabel = $pluralLabel;
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
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
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
     */
    public function setRoutes($routes)
    {
        $this->routes = $routes;
    }

    public function __serialize(): array
    {
        return [
            $this->id,
            $this->className,
            $this->label,
            $this->pluralLabel,
            $this->alias,
            $this->pluralAlias,
            $this->icon,
            $this->routes,
            $this->options,
            $this->fields
        ];
    }

    public function __unserialize(array $serialized): void
    {
        [
            $this->id,
            $this->className,
            $this->label,
            $this->pluralLabel,
            $this->alias,
            $this->pluralAlias,
            $this->icon,
            $this->routes,
            $this->options,
            $this->fields
        ] = $serialized;
    }
}
