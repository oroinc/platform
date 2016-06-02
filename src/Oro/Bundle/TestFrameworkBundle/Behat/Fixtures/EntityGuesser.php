<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

class EntityGuesser
{
    /**
     * @var EntityAliasResolver
     */
    protected $aliasResolver;

    /**
     * EntityGuesser constructor.
     * @param EntityAliasResolver $aliasResolver
     */
    public function __construct(EntityAliasResolver $aliasResolver)
    {
        $this->aliasResolver = $aliasResolver;
    }

    /**
     * @param string $entityName
     * @return string
     */
    public function guessEntityClass($entityName)
    {
        return $this->aliasResolver->getClassByAlias($this->singularize($entityName));
    }

    /**
     * @param string $entityName
     * @return string
     */
    protected function singularize($entityName)
    {
        $name = strtolower($entityName);
        $nameParts = explode(' ', $name);
        $nameParts = array_map([new Inflector, 'singularize'], $nameParts);

        return implode('', $nameParts);
    }
}
