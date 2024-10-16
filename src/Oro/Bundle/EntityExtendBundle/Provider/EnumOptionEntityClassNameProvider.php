<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;

/**
 * Decorator for EntityClassNameProvider to obtain EnumOption class name.
 */
class EnumOptionEntityClassNameProvider implements EntityClassNameProviderInterface
{
    public function __construct(private EntityClassNameProviderInterface $innerProvider)
    {
    }

    #[\Override]
    public function getEntityClassName(string $entityClass): ?string
    {
        $className = $this->innerProvider->getEntityClassName($entityClass);
        if (null === $className && $entityClass === EnumOption::class) {
            $className = EnumOption::class;
        }

        return $className;
    }

    #[\Override]
    public function getEntityClassPluralName(string $entityClass): ?string
    {
        return $this->innerProvider->getEntityClassPluralName($entityClass);
    }
}
