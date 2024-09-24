<?php

namespace Oro\Bundle\SecurityBundle\Attribute\Loader;

use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Metadata\AclAttributeStorage;
use Oro\Bundle\UIBundle\Provider\ControllerClassProvider;
use Oro\Component\Config\ResourcesContainerInterface;
use Oro\Component\PhpUtils\Attribute\Reader\AttributeReader;

/**
 * Loads ACL attributes from PHP classes of controllers.
 */
class AclAttributeLoader implements AclAttributeLoaderInterface
{
    private const ATTRIBUTE_CLASS = Acl::class;
    private const ANCESTOR_CLASS   = AclAncestor::class;

    public function __construct(
        private ControllerClassProvider $controllerClassProvider,
        private AttributeReader $reader
    ) {
    }

    #[\Override]
    public function load(AclAttributeStorage $storage, ResourcesContainerInterface $resourcesContainer): void
    {
        $controllerActions = [];
        $controllers = $this->controllerClassProvider->getControllers();
        foreach ($controllers as list($controller, $method)) {
            $controllerActions[$controller][] = $method;
        }
        $resourcesContainer->addResource($this->controllerClassProvider->getCacheResource());

        $processedClasses = [];
        foreach ($controllerActions as $class => $methods) {
            $parentClass = null;
            $classHierarchy = $this->getClassHierarchy($class);
            foreach ($classHierarchy as $className) {
                // class already processed
                if (array_key_exists($className, $processedClasses)) {
                    continue;
                }

                $initialBindings = $storage->getBindings($className);
                $storage->removeBindings($className);

                // copy parent class attribute bindings to current class bindings
                if ($parentClass && $storage->isKnownClass($parentClass)) {
                    foreach ($storage->getBindings($parentClass) as $method => $attributeName) {
                        $storage->addBinding($attributeName, $className, $method);
                    }
                }

                $this->loadClassAttributes($className, $storage);

                // apply initial bindings
                foreach ($initialBindings as $method => $attributeName) {
                    $storage->removeBinding($className, $method);
                    $storage->addBinding($attributeName, $className, $method);
                }

                $processedClasses[$className] = true;
                $parentClass = $className;
            }
        }
    }

    /**
     * @param string $className
     *
     * @return string[]
     */
    private function getClassHierarchy(string $className): array
    {
        $classHierarchy = array_reverse(class_parents($className));
        $classHierarchy[] = $className;

        return $classHierarchy;
    }

    /**
     * Loads attributes from given class.
     */
    private function loadClassAttributes(string $className, AclAttributeStorage $storage): void
    {
        $reflection = new \ReflectionClass($className);

        // read attributes from class
        $storage->removeBinding($reflection->getName());
        /** @var Acl|null $attribute */
        $attribute = $this->reader->getClassAttribute($reflection, static::ATTRIBUTE_CLASS);
        if ($attribute) {
            $storage->add($attribute, $reflection->getName());
        } else {
            /** @var AclAncestor|null $ancestor */
            $ancestor = $this->reader->getClassAttribute($reflection, static::ANCESTOR_CLASS);
            if ($ancestor) {
                $storage->addAncestor($ancestor, $reflection->getName());
            }
        }

        // read attributes from methods
        foreach ($reflection->getMethods() as $reflectionMethod) {
            $storage->removeBinding($reflection->getName(), $reflectionMethod->getName());
            /** @var Acl|null $attribute */
            $attribute = $this->reader->getMethodAttribute($reflectionMethod, static::ATTRIBUTE_CLASS);
            if ($attribute) {
                $storage->add($attribute, $reflection->getName(), $reflectionMethod->getName());
            } else {
                /** @var AclAncestor|null $ancestor */
                $ancestor = $this->reader->getMethodAttribute($reflectionMethod, static::ANCESTOR_CLASS);
                if ($ancestor) {
                    $storage->addAncestor($ancestor, $reflection->getName(), $reflectionMethod->getName());
                }
            }
        }
    }
}
