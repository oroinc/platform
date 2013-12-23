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
}
