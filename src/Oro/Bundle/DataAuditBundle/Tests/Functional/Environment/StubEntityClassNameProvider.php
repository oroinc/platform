<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Environment;

use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;

class StubEntityClassNameProvider implements EntityClassNameProviderInterface
{
    #[\Override]
    public function getEntityClassName(string $entityClass): ?string
    {
        return $this->isTestEntity($entityClass) ? $entityClass : null;
    }

    #[\Override]
    public function getEntityClassPluralName(string $entityClass): ?string
    {
        return $this->isTestEntity($entityClass) ? $entityClass : null;
    }

    private function isTestEntity(string $entityClass): bool
    {
        return
            TestAuditDataOwner::class === $entityClass
            || TestAuditDataChild::class === $entityClass
            || 'Extend\Entity\EV_Audit_Enum' === $entityClass
            || 'Extend\Entity\EV_Audit_Muenum' === $entityClass;
    }
}
