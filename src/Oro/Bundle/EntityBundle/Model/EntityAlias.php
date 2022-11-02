<?php

namespace Oro\Bundle\EntityBundle\Model;

/**
 * Information about an entity alias
 */
class EntityAlias
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

    public function __serialize(): array
    {
        return [$this->alias, $this->pluralAlias];
    }

    public function __unserialize(array $serialized): void
    {
        [$this->alias, $this->pluralAlias] = $serialized;
    }
}
