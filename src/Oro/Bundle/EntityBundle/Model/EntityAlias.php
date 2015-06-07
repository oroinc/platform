<?php

namespace Oro\Bundle\EntityBundle\Model;

class EntityAlias
{
    /** @var string */
    private $alias;

    /** @var string */
    private $pluralAlias;

    /**
     * @param string $alias
     * @param string $pluralAlias
     *
     * @throws \InvalidArgumentException if the given aliases are not valid
     */
    public function __construct($alias, $pluralAlias)
    {
        if (empty($alias)) {
            throw new \InvalidArgumentException('The entity alias should not be empty.');
        } elseif (!preg_match('/^[a-z][a-z0-9_]*$/D', $alias)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The "%s" string cannot be used as an entity alias '
                    . 'because it contains illegal characters. '
                    . 'The valid alias should start with a letter and only contain '
                    . 'lower case letters, numbers and underscores ("_").',
                    $alias
                )
            );
        }
        if (empty($pluralAlias)) {
            throw new \InvalidArgumentException('The entity plural alias should not be empty.');
        } elseif (!preg_match('/^[a-z][a-z0-9_]*$/D', $alias)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The "%s" string cannot be used as an entity plural alias '
                    . 'because it contains illegal characters. '
                    . 'The valid alias should start with a letter and only contain '
                    . 'lower case letters, numbers and underscores ("_").',
                    $pluralAlias
                )
            );
        }

        $this->alias       = $alias;
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
}
