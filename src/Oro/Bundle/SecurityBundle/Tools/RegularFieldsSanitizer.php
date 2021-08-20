<?php

namespace Oro\Bundle\SecurityBundle\Tools;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Sanitizes entity fields of specified type.
 *
 * THIS CLASS MUST BE USED WITH CAUTION AS IT CAN MAKE MASS MODIFICATION OF USER CONTENT!
 */
class RegularFieldsSanitizer extends AbstractFieldsSanitizer
{
    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    protected function getFieldsToSanitize(ClassMetadataInfo $classMetadata, string $fieldTypeToSanitize): array
    {
        $fields = [];
        foreach ($classMetadata->getFieldNames() as $name) {
            if ($classMetadata->getTypeOfField($name) === $fieldTypeToSanitize) {
                $fields[] = $name;
            }
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRowsToSanitizeQueryBuilder(ClassMetadataInfo $classMetadata, array $fields): QueryBuilder
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass($classMetadata->getName());
        $qb = $entityManager
            ->getRepository($classMetadata->getName())
            ->createQueryBuilder('entity')
            ->select(QueryBuilderUtil::sprintf('entity.%s as id', $classMetadata->getSingleIdentifierFieldName()));

        foreach ($fields as $fieldName) {
            $qbFieldName = QueryBuilderUtil::sprintf('entity.%s', $fieldName);
            $qb
                ->addSelect($qbFieldName)
                ->orWhere(
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull($qbFieldName),
                        $qb->expr()->neq($qbFieldName, ':empty')
                    )
                )
                ->setParameter('empty', '');
        }

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    protected function sanitizeEntityRow(
        ClassMetadataInfo $classMetadata,
        array $row,
        array $fields,
        int $mode,
        array $modeArguments,
        bool $applyChanges
    ): array {
        $className = $classMetadata->getName();

        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass($className);

        $updateQb = $entityManager->createQueryBuilder()->update($className, 'entity');
        $affectedFields = [];

        foreach ($fields as $fieldName) {
            if (empty($row[$fieldName])) {
                // Skips if field is empty.
                continue;
            }

            $sanitizedValue = $this->sanitizeText((string)$row[$fieldName], $mode, $modeArguments);
            if ($sanitizedValue === $row[$fieldName]) {
                // Skips field if data is not changed after sanitizing.
                continue;
            }

            $placeholder = QueryBuilderUtil::sprintf(':value_%s', $fieldName);
            $updateQb
                ->set(QueryBuilderUtil::sprintf('entity.%s', $fieldName), $placeholder)
                ->setParameter($placeholder, $sanitizedValue, Types::TEXT);

            $affectedFields[] = $fieldName;
        }

        if ($applyChanges && $affectedFields) {
            $updateQb
                ->where(
                    $updateQb->expr()->eq(
                        QueryBuilderUtil::sprintf('entity.%s', $classMetadata->getSingleIdentifierFieldName()),
                        ':entity_id'
                    )
                )
                ->setParameter('entity_id', $row['id'])
                ->getQuery()->execute();
        }

        return $affectedFields;
    }
}
