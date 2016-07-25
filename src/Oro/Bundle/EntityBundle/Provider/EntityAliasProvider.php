<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Inflector\Inflector;

use Oro\Bundle\EntityBundle\Model\EntityAlias;

class EntityAliasProvider implements EntityAliasProviderInterface, EntityClassProviderInterface
{
    /** @var EntityAliasConfigBag */
    protected $config;

    /**
     * @param EntityAliasConfigBag $config
     */
    public function __construct(EntityAliasConfigBag $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAlias($entityClass)
    {
        // check for the exclusion list
        if ($this->config->isEntityAliasExclusionExist($entityClass)) {
            return false;
        }

        // check for explicitly configured aliases
        if ($this->config->hasEntityAlias($entityClass)) {
            return $this->config->getEntityAlias($entityClass);
        }

        // check Gedmo translatable entities
        if (is_a($entityClass, 'Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation', true)) {
            return false;
        }
        if (is_a($entityClass, 'Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation', true)) {
            return false;
        }

        // generate default aliases
        $name = $this->getEntityName($entityClass);

        return new EntityAlias(
            strtolower($name),
            strtolower(Inflector::pluralize($name))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getClassNames()
    {
        return $this->config->getClassNames();
    }

    /**
     * @param string $entityClass
     *
     * @return string
     */
    protected function getEntityName($entityClass)
    {
        return $this->isOroEntity($entityClass)
            ? $this->getOroName($entityClass)
            : $this->getExternalName($entityClass);
    }

    /**
     * Determines whether the given entity is from one of Oro bundles.
     *
     * @param string $entityClass
     *
     * @return bool
     */
    protected function isOroEntity($entityClass)
    {
        return $this->startsWith($entityClass, 'Oro');
    }

    /**
     * Returns a string which is used to build entity aliases for entities from Oro bundles
     *
     * @param string $entityClass
     *
     * @return string
     */
    protected function getOroName($entityClass)
    {
        return str_replace('_', '', $this->getShortClassName($entityClass));
    }

    /**
     * Returns a string which is used to build entity aliases for entities from 3-rd party bundles
     *
     * @param string $entityClass
     *
     * @return string
     */
    protected function getExternalName($entityClass)
    {
        $name      = null;
        $parts     = explode('\\', $entityClass);
        $partCount = count($parts);
        if ($partCount > 3) {
            $bundlePart = $parts[$partCount - 3];
            if ($this->endsWith($bundlePart, 'Bundle')) {
                $bundleName = substr($bundlePart, 0, strlen($bundlePart) - 6);
                if (!$this->startsWith($parts[$partCount - 1], $bundleName)) {
                    $name = $bundleName . $parts[$partCount - 1];
                }

            }
        }
        if (!$name) {
            $name = $parts[$partCount - 1];
        }

        return str_replace('_', '', $name);
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

    /**
     * Determines whether the beginning of $haystack matches $needle.
     *
     * @param string $haystack The string to check
     * @param string $needle   The string to compare
     *
     * @return bool
     */
    protected function startsWith($haystack, $needle)
    {
        return strpos($haystack, $needle) === 0;
    }

    /**
     * Determines whether the ending of $haystack matches $needle.
     *
     * @param string $haystack The string to check
     * @param string $needle   The string to compare
     *
     * @return bool
     */
    protected function endsWith($haystack, $needle)
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }
}
