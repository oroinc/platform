<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Inflector\Inflector;

use Oro\Bundle\EntityBundle\Model\EntityAlias;

class EntityAliasProvider implements EntityAliasProviderInterface
{
    /** @var array */
    protected $entityAliases;

    /** @var array */
    protected $exclusions;

    /**
     * @param array $entityAliases
     * @param array $exclusions
     */
    public function __construct(array $entityAliases, array $exclusions)
    {
        $this->entityAliases = $entityAliases;
        $this->exclusions    = array_fill_keys($exclusions, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAlias($entityClass)
    {
        if (isset($this->exclusions[$entityClass])) {
            return false;
        }

        if (isset($this->entityAliases[$entityClass])) {
            return new EntityAlias(
                $this->entityAliases[$entityClass]['alias'],
                $this->entityAliases[$entityClass]['plural_alias']
            );
        } else {
            $name = str_replace('_', '', $this->getShortClassName($entityClass));

            return new EntityAlias(
                strtolower($name),
                strtolower(Inflector::pluralize($name))
            );
        }
    }

    /**
     * Gets the short name of the class, the part without the namespace.
     *
     * @param string $className The full name of a class
     *
     * @return string
     */
    protected function getShortClassName($className)
    {
        $lastDelimiter = strrpos($className, '\\');

        return false === $lastDelimiter
            ? $className
            : substr($className, $lastDelimiter + 1);
    }
}
