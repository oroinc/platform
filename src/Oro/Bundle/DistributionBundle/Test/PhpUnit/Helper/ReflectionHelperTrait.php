<?php

namespace Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper;

trait ReflectionHelperTrait
{
    /**
     * @param mixed $class
     * @param mixed $subclass
     */
    public function assertSubclassOf($class, $subclass)
    {
        $rc = new \ReflectionClass($subclass);
        $this->assertTrue($rc->isSubclassOf($class));
    }

    /**
     * @param string|object $classOrObject
     * @param string $attributeName
     * @param mixed $attributeValue
     */
    public function writeAttribute($classOrObject, $attributeName, $attributeValue)
    {
        $rp = new \ReflectionProperty($classOrObject, $attributeName);
        $rp->setAccessible(true);
        $rp->setValue($classOrObject, $attributeValue);
        $rp->setAccessible(false);
    }

}
