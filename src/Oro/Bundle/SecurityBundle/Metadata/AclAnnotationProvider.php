<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Annotation\Loader\AclAnnotationLoaderInterface;
use Oro\Component\Config\Cache\PhpConfigProvider;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * The provider for ACL annotations configuration
 * that are loaded from "Resources/config/oro/acls.yml" files and PHP classes of controllers.
 */
class AclAnnotationProvider extends PhpConfigProvider
{
    /** @var iterable|AclAnnotationLoaderInterface[] */
    private $loaders;

    /** @var EntityClassResolver */
    private $entityClassResolver;

    /**
     * @param string                                  $cacheFile
     * @param bool                                    $debug
     * @param EntityClassResolver                     $entityClassResolver
     * @param iterable|AclAnnotationLoaderInterface[] $loaders
     */
    public function __construct(
        string $cacheFile,
        bool $debug,
        EntityClassResolver $entityClassResolver,
        iterable $loaders
    ) {
        parent::__construct($cacheFile, $debug);
        $this->entityClassResolver = $entityClassResolver;
        $this->loaders = $loaders;
    }

    /**
     * Gets an annotation by its ID.
     *
     * @param string $id
     *
     * @return AclAnnotation|null AclAnnotation object or NULL if ACL annotation was not found
     */
    public function findAnnotationById(string $id): ?AclAnnotation
    {
        return $this->getStorage()->findById($id);
    }

    /**
     * Gets ACL annotation is bound to the given class/method.
     *
     * @param string      $class
     * @param string|null $method
     *
     * @return AclAnnotation|null AclAnnotation object or NULL if ACL annotation was not found
     */
    public function findAnnotation(string $class, string $method = null): ?AclAnnotation
    {
        return $this->getStorage()->find($class, $method);
    }

    /**
     * Determines whether the given class/method has ACL annotation.
     */
    public function hasAnnotation(string $class, string $method = null): bool
    {
        return $this->getStorage()->has($class, $method);
    }

    /**
     * Gets ACL annotations.
     *
     * @param string|null $type The annotation type
     *
     * @return AclAnnotation[]
     */
    public function getAnnotations(string $type = null): array
    {
        return $this->getStorage()->getAnnotations($type);
    }

    /**
     * Checks whether the given class or at least one of its method is protected by ACL security policy.
     *
     * @param string $class
     *
     * @return bool TRUE if the class is protected by ACL; otherwise, FALSE
     */
    public function isProtectedClass(string $class): bool
    {
        return $this->getStorage()->isKnownClass($class);
    }

    /**
     * Checks whether the given method of the given class is protected by ACL security policy.
     *
     * @param string $class
     * @param string $method
     *
     * @return bool TRUE if the method is protected by ACL; otherwise, FALSE
     */
    public function isProtectedMethod(string $class, string $method): bool
    {
        return $this->getStorage()->isKnownMethod($class, $method);
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $storage = new AclAnnotationStorage();
        foreach ($this->loaders as $loader) {
            $loader->load($storage, $resourcesContainer);
        }

        /**
         * resolve entity names here to increase performance of AclExtensionSelector
         * @see \Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector
         */
        $annotations = $storage->getAnnotations();
        foreach ($annotations as $annotation) {
            if (EntityAclExtension::NAME === $annotation->getType()) {
                $annotation->setClass($this->entityClassResolver->getEntityClass($annotation->getClass()));
            }
        }

        return $storage;
    }

    /**
     * {@inheritdoc}
     */
    protected function assertLoaderConfig($config): void
    {
        if (!$config instanceof AclAnnotationStorage) {
            throw new \LogicException(\sprintf('Expected instance of %s.', AclAnnotationStorage::class));
        }
    }

    private function getStorage(): AclAnnotationStorage
    {
        return $this->doGetConfig();
    }
}
