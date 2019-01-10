<?php

namespace Oro\Bundle\EntityBundle\Model;

/**
 * Represents detailed information about an entity type.
 */
class EntityStructure implements \Serializable
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
     *
     * @return self
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
     * @return self
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
     * @return self
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
     * @return self
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
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
     *
     * @return self
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
     * @return self
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
     *
     * @return self
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
     * @return self
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
     * @return self
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
     * @return self
     */
    public function setRoutes($routes)
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
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
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
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
            ) = unserialize($serialized, ['allowed_classes' => [EntityFieldStructure::class]]);
    }
}
