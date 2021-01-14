<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Persistence\Mapping\Driver\MappingDriverChain as BaseMappingDriverChain;

/**
 * Adds a memory cache for the result value of isTransient() method.
 */
class MappingDriverChain extends BaseMappingDriverChain
{
    /** @var bool[] */
    private $isTransientCache = [];

    /**
     * {@inheritdoc}
     */
    public function isTransient($className)
    {
        if (isset($this->isTransientCache[$className])) {
            return $this->isTransientCache[$className];
        }

        $result = parent::isTransient($className);
        $this->isTransientCache[$className] = $result;

        return $result;
    }
}
