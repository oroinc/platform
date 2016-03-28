<?php

namespace Oro\Bundle\EntityBundle\Model;

class EntityAlias implements \Serializable
{
    /** @var string */
    private $alias;

    /** @var string */
    private $pluralAlias;

    /**
     * @param string $alias
     * @param string $pluralAlias
     */
    public function __construct($alias, $pluralAlias)
    {
        $this->alias = $alias;
        $this->pluralAlias = $pluralAlias;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getPluralAlias()
    {
        return $this->pluralAlias;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([$this->alias, $this->pluralAlias]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($this->alias, $this->pluralAlias) = unserialize($serialized);
    }
}
