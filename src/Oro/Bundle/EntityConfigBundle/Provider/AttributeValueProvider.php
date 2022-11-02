<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\PlatformBundle\Provider\DbalTypeDefaultValueProvider;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Sets attributes of the specified {@see AttributeFamily} to either NULL or default value specific
 * to the field or its column's DBAL type.
 */
class AttributeValueProvider implements AttributeValueProviderInterface
{
    private ManagerRegistry $managerRegistry;

    private DbalTypeDefaultValueProvider $dbalTypeDefaultValueProvider;

    public function __construct(
        ManagerRegistry $managerRegistry,
        DbalTypeDefaultValueProvider $dbalTypeDefaultValueProvider
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->dbalTypeDefaultValueProvider = $dbalTypeDefaultValueProvider;
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @param string[] $names
     */
    public function removeAttributeValues(AttributeFamily $attributeFamily, array $names): void
    {
        $entityClass = $attributeFamily->getEntityClass();
        $entityManager = $this->managerRegistry->getManagerForClass($entityClass);

        $queryBuilder = $entityManager->createQueryBuilder();
        $queryBuilder
            ->update($entityClass, 'entity')
            ->where($queryBuilder->expr()->eq('entity.attributeFamily', ':attributeFamily'))
            ->setParameter('attributeFamily', $attributeFamily);

        $classMetadata = $entityManager->getClassMetadata($entityClass);
        $doExecute = false;
        foreach ($names as $name) {
            if ($classMetadata->hasField($name)) {
                $mapping = $classMetadata->getFieldMapping($name);
                $default = $mapping['default'] ?? null;

                if (!isset($default) && !$classMetadata->isNullable($name)) {
                    if (!$this->dbalTypeDefaultValueProvider->hasDefaultValueForDbalType($mapping['type'])) {
                        // Skips because default value cannot be found.
                        continue;
                    }

                    $default = $this->dbalTypeDefaultValueProvider->getDefaultValueForDbalType($mapping['type']);
                }
            } elseif ($classMetadata->hasAssociation($name)) {
                $mapping = $classMetadata->getAssociationMapping($name);
                if (!($mapping['type'] & ClassMetadataInfo::TO_ONE)) {
                    // Skips because to-many association type is not supported yet.
                    continue;
                }

                $default = null;
            } else {
                // Skips because field or association is missing.
                continue;
            }

            $parameterName = QueryBuilderUtil::sprintf(':%s', $name);
            $queryBuilder
                ->set(QueryBuilderUtil::getField('entity', $name), $parameterName)
                ->setParameter($parameterName, $default);
            $doExecute = true;
        }

        if ($doExecute) {
            $queryBuilder->getQuery()->execute();
        }
    }
}
