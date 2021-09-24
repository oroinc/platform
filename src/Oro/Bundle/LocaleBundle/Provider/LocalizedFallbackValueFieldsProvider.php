<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

/**
 * Provides an array of localized fallback value field names for the specified entity class.
 */
class LocalizedFallbackValueFieldsProvider
{
    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Provides an array of localized fallback value field names for the specified entity class.
     * Target entity should be a descendant of Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue.
     *
     * @param string $className
     *
     * @return string[]
     */
    public function getLocalizedFallbackValueFields(string $className): array
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass($className);
        $classMetadata = $entityManager->getClassMetadata($className);

        $fields = [];
        foreach ($classMetadata->getAssociationNames() as $name) {
            if (is_a($classMetadata->getAssociationTargetClass($name), AbstractLocalizedFallbackValue::class, true)) {
                $fields[] = $name;
            }
        }

        return $fields;
    }
}
