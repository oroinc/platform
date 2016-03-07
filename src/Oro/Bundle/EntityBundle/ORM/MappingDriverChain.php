<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain as BaseMappingDriverChain;

class MappingDriverChain extends BaseMappingDriverChain
{
    /** @var bool[] */
    protected $isTransientCache = [];

    /**
     * {@inheritdoc}
     */
    public function isTransient($className)
    {
        if (array_key_exists($className, $this->isTransientCache)) {
            return $this->isTransientCache[$className];
        }

        $result = parent::isTransient($className);

        $this->isTransientCache[$className] = $result;

        return $result;
    }
}
