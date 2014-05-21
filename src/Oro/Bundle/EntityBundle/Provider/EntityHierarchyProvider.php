<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class EntityHierarchyProvider
{
    /** @var  EntityManager */
    protected $entityManager;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    public function __construct(
        EntityManager $entityManager,
        EntityClassResolver $entityClassResolver,
        ConfigProvider $entityConfigProvider
    ) {
        $this->entityManager        = $entityManager;
        $this->entityClassResolver  = $entityClassResolver;
        $this->entityConfigProvider = $entityConfigProvider;
    }

    /**
     * @return array
     */
    public function getHierarchy()
    {
        $hierarchy = [];

        /** ConfigIdInterface[] */
        $entities = $this->entityConfigProvider->getIds();
        foreach ($entities as $entity) {
            $className = $entity->getClassName();
            if ($parents = $this->getParents($className)) {
                $hierarchy[$className] = $parents;
            }
        }

        return $hierarchy;
    }

    /**
     * @param string $className
     * @return array
     */
    public function getHierarchyForClassName($className)
    {
        $hierarchy = $this->getHierarchy();
        if (isset($hierarchy[$className])) {
            return $hierarchy[$className];
        }

        return [];
    }

    /**
     * Returns parent doctrine entities for given entity class name
     *
     * @param       $className
     * @param array $parents
     * @return array
     */
    protected function getParents($className, $parents = [])
    {
        $reflection = new \ReflectionClass($className);
        $parentClass = $reflection->getParentClass();
        if ($parentClass && $this->entityClassResolver->isEntity($parentClass->getName())) {
            /** @var ClassMetadata $metadata */
            $metadata = $this->entityManager->getClassMetadata($parentClass->getName());
            if ($metadata->isMappedSuperclass) {
                $parents = $this->getParents(
                    $parentClass->getName(),
                    $parents = array_merge(
                        $parents,
                        [$parentClass->getName()]
                    )
                );
            }
        }

        return $parents;
    }
}
