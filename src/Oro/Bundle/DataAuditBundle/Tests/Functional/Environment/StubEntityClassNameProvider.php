<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Environment;

use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;

class StubEntityClassNameProvider implements EntityClassNameProviderInterface
{
    public function getEntityClassName($entityClass)
    {
        return $entityClass;
    }

    public function getEntityClassPluralName($entityClass)
    {
        return $entityClass;
    }
}
