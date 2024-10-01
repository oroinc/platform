<?php

namespace Oro\Bundle\SoapBundle\Routing;

use Doctrine\Common\Annotations\Reader;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * Sets name="" for FOS Rest routing annotations.
 * @link https://github.com/FriendsOfSymfony/FOSRestBundle/issues/1086
 */
class RestAnnotationReader implements Reader
{
    /** @var Reader */
    protected $innerReader;

    public function __construct(Reader $innerReader)
    {
        $this->innerReader = $innerReader;
    }

    #[\Override]
    public function getClassAnnotations(\ReflectionClass $class)
    {
        return $this->innerReader->getClassAnnotations($class);
    }

    #[\Override]
    public function getClassAnnotation(\ReflectionClass $class, $annotationName)
    {
        return $this->innerReader->getClassAnnotation($class, $annotationName);
    }

    #[\Override]
    public function getMethodAnnotations(\ReflectionMethod $method)
    {
        $annotations = [];

        $srcAnnotations = $this->innerReader->getMethodAnnotations($method);
        foreach ($srcAnnotations as $annotation) {
            $annotations[] = $this->processMethodAnnotation($annotation);
        }

        return $annotations;
    }

    #[\Override]
    public function getMethodAnnotation(\ReflectionMethod $method, $annotationName)
    {
        $annotation = $this->innerReader->getMethodAnnotation($method, $annotationName);
        if (null === $annotation) {
            return null;
        }

        return $this->processMethodAnnotation($annotation);
    }

    #[\Override]
    public function getPropertyAnnotations(\ReflectionProperty $property)
    {
        return $this->innerReader->getPropertyAnnotations($property);
    }

    #[\Override]
    public function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        return $this->innerReader->getPropertyAnnotation($property, $annotationName);
    }

    /**
     * @param object $annotation
     *
     * @return object
     */
    protected function processMethodAnnotation($annotation)
    {
        if ($this->isRestMethodAnnotation($annotation) && null === $annotation->getName()) {
            $annotation->setName('');
        }

        return $annotation;
    }

    /**
     * @param object $annotation
     *
     * @return bool
     */
    protected function isRestMethodAnnotation($annotation)
    {
        if ($annotation instanceof Rest\Route) {
            if ($annotation instanceof Rest\Get) {
                return true;
            }
            if ($annotation instanceof Rest\Post) {
                return true;
            }
            if ($annotation instanceof Rest\Put) {
                return true;
            }
            if ($annotation instanceof Rest\Delete) {
                return true;
            }
            if ($annotation instanceof Rest\Patch) {
                return true;
            }
        }

        return false;
    }
}
