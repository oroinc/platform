<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Attribute\Acl as AclAttribute;
use Oro\Bundle\SecurityBundle\Attribute\Loader\AclAttributeLoaderInterface;
use Oro\Component\Config\Cache\PhpConfigProvider;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * The provider for ACL attributes configuration
 * that are loaded from "Resources/config/oro/acls.yml" files and PHP classes of controllers.
 */
class AclAttributeProvider extends PhpConfigProvider
{
    /** @var iterable|AclAttributeLoaderInterface[] */
    private $loaders;

    /** @var EntityClassResolver */
    private $entityClassResolver;

    /**
     * @param string                                  $cacheFile
     * @param bool                                    $debug
     * @param EntityClassResolver                     $entityClassResolver
     * @param iterable|AclAttributeLoaderInterface[] $loaders
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
     * Gets an attribute by its ID.
     *
     * @param string $id
     *
     * @return AclAttribute|null AclAttribute object or NULL if ACL attribute was not found
     */
    public function findAttributeById(string $id): ?AclAttribute
    {
        return $this->getStorage()->findById($id);
    }

    /**
     * Gets ACL attribute is bound to the given class/method.
     *
     * @param string      $class
     * @param string|null $method
     *
     * @return AclAttribute|null AclAttribute object or NULL if ACL attribute was not found
     */
    public function findAttribute(string $class, string $method = null): ?AclAttribute
    {
        return $this->getStorage()->find($class, $method);
    }

    /**
     * Determines whether the given class/method has ACL attribute.
     */
    public function hasAttribute(string $class, string $method = null): bool
    {
        return $this->getStorage()->has($class, $method);
    }

    /**
     * Gets ACL attributes.
     *
     * @param string|null $type The attribute type
     *
     * @return AclAttribute[]
     */
    public function getAttributes(string $type = null): array
    {
        return $this->getStorage()->getAttributes($type);
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

    #[\Override]
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $storage = new AclAttributeStorage();
        foreach ($this->loaders as $loader) {
            $loader->load($storage, $resourcesContainer);
        }

        /**
         * resolve entity names here to increase performance of AclExtensionSelector
         * @see \Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector
         */
        $attributes = $storage->getAttributes();
        foreach ($attributes as $attribute) {
            if (EntityAclExtension::NAME === $attribute->getType()) {
                $attribute->setClass($this->entityClassResolver->getEntityClass($attribute->getClass()));
            }
        }

        return $storage;
    }

    #[\Override]
    protected function assertLoaderConfig($config): void
    {
        if (!$config instanceof AclAttributeStorage) {
            throw new \LogicException(\sprintf('Expected instance of %s.', AclAttributeStorage::class));
        }
    }

    private function getStorage(): AclAttributeStorage
    {
        return $this->doGetConfig();
    }
}
