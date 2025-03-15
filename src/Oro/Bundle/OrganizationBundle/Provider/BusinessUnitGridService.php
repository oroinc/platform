<?php

namespace Oro\Bundle\OrganizationBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

/**
 * Provides choices for business unit grid filter
 */
class BusinessUnitGridService
{
    private array $choices = [];

    public function __construct(
        private ManagerRegistry $doctrine
    ) {
    }

    public function getOwnerChoices(): array
    {
        return $this->getChoices('name', BusinessUnit::class);
    }

    protected function getChoices(string $field, string $entity, string $alias = 'bu'): array
    {
        $key = $entity . '|' . $field;
        if (!isset($this->choices[$key])) {
            $choices = $this->doctrine->getRepository(BusinessUnit::class)
                ->getGridFilterChoices($field, $entity, $alias);
            $this->choices[$key] = array_flip($choices);
        }

        return $this->choices[$key];
    }
}
