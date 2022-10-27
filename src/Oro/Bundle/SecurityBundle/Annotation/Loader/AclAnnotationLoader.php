<?php

namespace Oro\Bundle\SecurityBundle\Annotation\Loader;

use Doctrine\Common\Annotations\Reader as AnnotationReader;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationStorage;
use Oro\Bundle\UIBundle\Provider\ControllerClassProvider;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * Loads ACL annotations from PHP classes of controllers.
 */
class AclAnnotationLoader implements AclAnnotationLoaderInterface
{
    private const ANNOTATION_CLASS = Acl::class;
    private const ANCESTOR_CLASS   = AclAncestor::class;

    /** @var ControllerClassProvider */
    private $controllerClassProvider;

    /** @var AnnotationReader */
    private $reader;

    public function __construct(ControllerClassProvider $controllerClassProvider, AnnotationReader $reader)
    {
        $this->controllerClassProvider = $controllerClassProvider;
        $this->reader = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public function load(AclAnnotationStorage $storage, ResourcesContainerInterface $resourcesContainer): void
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

                // copy parent class annotation bindings to current class bindings
                if ($parentClass && $storage->isKnownClass($parentClass)) {
                    foreach ($storage->getBindings($parentClass) as $method => $annotationName) {
                        $storage->addBinding($annotationName, $className, $method);
                    }
                }

                $this->loadClassAnnotations($className, $storage);

                // apply initial bindings
                foreach ($initialBindings as $method => $annotationName) {
                    $storage->removeBinding($className, $method);
                    $storage->addBinding($annotationName, $className, $method);
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
     * Loads annotations from given class.
     */
    private function loadClassAnnotations(string $className, AclAnnotationStorage $storage): void
    {
        $reflection = new \ReflectionClass($className);

        // read annotations from class
        $storage->removeBinding($reflection->getName());
        /** @var Acl|null $annotation */
        $annotation = $this->reader->getClassAnnotation($reflection, self::ANNOTATION_CLASS);
        if ($annotation) {
            $storage->add($annotation, $reflection->getName());
        } else {
            /** @var AclAncestor|null $ancestor */
            $ancestor = $this->reader->getClassAnnotation($reflection, self::ANCESTOR_CLASS);
            if ($ancestor) {
                $storage->addAncestor($ancestor, $reflection->getName());
            }
        }

        // read annotations from methods
        foreach ($reflection->getMethods() as $reflectionMethod) {
            $storage->removeBinding($reflection->getName(), $reflectionMethod->getName());
            /** @var Acl|null $annotation */
            $annotation = $this->reader->getMethodAnnotation($reflectionMethod, self::ANNOTATION_CLASS);
            if ($annotation) {
                $storage->add($annotation, $reflection->getName(), $reflectionMethod->getName());
            } else {
                /** @var AclAncestor|null $ancestor */
                $ancestor = $this->reader->getMethodAnnotation($reflectionMethod, self::ANCESTOR_CLASS);
                if ($ancestor) {
                    $storage->addAncestor($ancestor, $reflection->getName(), $reflectionMethod->getName());
                }
            }
        }
    }
}
