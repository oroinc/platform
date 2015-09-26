<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

class EntityChecker
{
    /** @var EntityManagerBag */
    protected $entityManagerBag;

    /** @var array */
    protected $entities = [];

    /** @var array */
    protected $fields = [];

    /**
     * @param EntityManagerBag $entityManagerBag
     */
    public function __construct(EntityManagerBag $entityManagerBag)
    {
        $this->entityManagerBag = $entityManagerBag;
    }

    /**
     * Checks whether the given class is an entity might be configurable.
     *
     * @param string $className
     *
     * @return bool
     */
    public function isEntity($className)
    {
        if (isset($this->entities[$className])) {
            return $this->entities[$className];
        }

        $result = false;
        foreach ($this->entityManagerBag->getEntityManagers() as $em) {
            if (!$em->getMetadataFactory()->isTransient($className)) {
                $result = true;
                break;
            }
        }

        $this->entities[$className] = $result;

        return $result;
    }

    /**
     * Checks whether the given entity field might be configurable.
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return bool
     */
    public function isField($className, $fieldName)
    {
        if (!$this->isEntity($className)) {
            return false;
        }

        if (isset($this->fields[$className][$fieldName])) {
            return $this->fields[$className][$fieldName];
        }

        $result = false;
        foreach ($this->entityManagerBag->getEntityManagers() as $em) {
            $metadataFactory = $em->getMetadataFactory();
            if (!$metadataFactory->isTransient($className)) {
                $metadata = $metadataFactory->getMetadataFor($className);
                if ($metadata->hasField($fieldName) || $metadata->hasAssociation($fieldName)) {
                    $result = true;
                    break;
                }
            }
        }

        $this->fields[$className][$fieldName] = $result;

        return $result;
    }

    public function clear()
    {
        $this->entities = [];
        $this->fields   = [];
    }
}
