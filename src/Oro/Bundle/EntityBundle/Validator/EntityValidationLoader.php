<?php

namespace Oro\Bundle\EntityBundle\Validator;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata as EntityClassMetadata;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\YamlFileLoader;

/**
 * A workaround to warm up validation metadata cache for all entities, their proxies and some ORM related classes.
 * We have to extend this class from YamlFileLoader because there is not interface for getMappedClasses() method
 * and usage of this method is hardcoded in ValidatorCacheWarmer.
 * @see \Symfony\Bundle\FrameworkBundle\CacheWarmer\ValidatorCacheWarmer::extractSupportedLoaders
 */
class EntityValidationLoader extends YamlFileLoader
{
    private ManagerRegistry $doctrine;
    private ?array $mappedClasses = null;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[\Override]
    public function loadClassMetadata(ClassMetadata $metadata): bool
    {
        return false;
    }

    /**
     * Gets the names of the classes mapped in all entity managers.
     *
     * @return string[]
     */
    #[\Override]
    public function getMappedClasses(): array
    {
        if (null === $this->mappedClasses) {
            $this->mappedClasses = $this->loadMappedClasses();
        }

        return $this->mappedClasses;
    }

    private function loadMappedClasses(): array
    {
        $mappedClasses = [];
        $managers = $this->doctrine->getManagers();
        foreach ($managers as $manager) {
            if (!$manager instanceof EntityManagerInterface) {
                continue;
            }
            $entityProxyNamespace = $manager->getConfiguration()->getProxyNamespace();
            $autoGenerateProxyClasses = $manager->getConfiguration()->getAutoGenerateProxyClasses();
            $entityMetadatas = $manager->getMetadataFactory()->getAllMetadata();
            foreach ($entityMetadatas as $entityMetadata) {
                $entityClass = $entityMetadata->getName();
                $mappedClasses[] = $entityClass;
                if (!$autoGenerateProxyClasses && !$this->isSkipProxyClass($entityMetadata)) {
                    $entityProxyClass = ClassUtils::generateProxyClassName($entityClass, $entityProxyNamespace);
                    if (class_exists($entityProxyClass)) {
                        $mappedClasses[] = $entityProxyClass;
                    }
                }
            }
        }
        $mappedClasses[] = PersistentCollection::class;
        $mappedClasses[] = \Doctrine\ORM\Proxy\Proxy::class;
        $mappedClasses[] = \Doctrine\Persistence\Proxy::class;
        $mappedClasses[] = \Doctrine\Common\Proxy\Proxy::class;

        return $mappedClasses;
    }

    private function isSkipProxyClass(EntityClassMetadata $metadata): bool
    {
        return
            $metadata->isMappedSuperclass
            || $metadata->isEmbeddedClass
            || $metadata->getReflectionClass()->isAbstract();
    }
}
