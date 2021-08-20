<?php

namespace Oro\Bundle\LocaleBundle\Tools;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Provider\LocalizedFallbackValueFieldsProvider;
use Oro\Bundle\SecurityBundle\Tools\AbstractFieldsSanitizer;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Sanitizes localized fallback values fields of specified type.
 *
 * THIS CLASS MUST BE USED WITH CAUTION AS IT CAN MAKE MASS MODIFICATION OF USER CONTENT!
 */
class LocalizedFieldsSanitizer extends AbstractFieldsSanitizer
{
    private LocalizedFallbackValueFieldsProvider $localizedFieldsProvider;

    public function __construct(
        ManagerRegistry $managerRegistry,
        HtmlTagHelper $htmlTagHelper,
        LocalizedFallbackValueFieldsProvider $localizedFallbackValueFieldsProvider
    ) {
        parent::__construct($managerRegistry, $htmlTagHelper);

        $this->localizedFieldsProvider = $localizedFallbackValueFieldsProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFieldsToSanitize(ClassMetadataInfo $classMetadata, string $fieldTypeToSanitize): array
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass($classMetadata->getName());
        $fields = [];
        $localizedFields = $this->localizedFieldsProvider->getLocalizedFallbackValueFields($classMetadata->getName());
        foreach ($localizedFields as $associationName) {
            $targetClass = $classMetadata->getAssociationTargetClass($associationName);
            $lfvClassMetadata = $entityManager->getClassMetadata($targetClass);
            foreach ($lfvClassMetadata->getFieldNames() as $fieldName) {
                if ($lfvClassMetadata->getTypeOfField($fieldName) === $fieldTypeToSanitize) {
                    $fields[$associationName][] = $fieldName;
                }
            }
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
     *
     * @return array An associative array of LFV association names and their fields that need sanitizing.
     *  [
     *      string $associationName => [ // Name of association to localized fallback values
     *          string $fieldName1, // Name of field for sanitizing inside localized fallback value
     *          string $fieldName2,
     *          // ...
     *      ],
     *      // ...
     *  ]
     */
    protected function getRowsToSanitize(ClassMetadataInfo $classMetadata, array $fields, int $chunkSize): iterable
    {
        $className = $classMetadata->getName();

        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass($className);
        $idField = $classMetadata->getSingleIdentifierFieldName();

        foreach ($fields as $associationName => $fieldNames) {
            $lfvClassMetadata = $entityManager->getClassMetadata(
                $classMetadata->getAssociationTargetClass($associationName)
            );

            $qb = $this
                ->getRowsToSanitizeQueryBuilder($lfvClassMetadata, $fieldNames)
                // Exchange "from" and "join" to avoid using isMemberOf() when getting parent entity id for each
                // localized fallback value row.
                ->resetDQLPart('from')
                ->from($className, 'parent_entity')
                ->innerJoin(QueryBuilderUtil::sprintf('parent_entity.%s', $associationName), 'lfv')
                ->addSelect(QueryBuilderUtil::sprintf('parent_entity.%s as id', $idField));

            foreach ($this->getIterable($qb->getQuery(), $chunkSize) as $row) {
                yield ['id' => $row['id'], 'association' => $associationName, 'data' => $row];
            }
        }
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
            ->createQueryBuilder('lfv')
            ->select('lfv.id as lfv_id');

        foreach ($fields as $fieldName) {
            $qbField = QueryBuilderUtil::sprintf('lfv.%s', $fieldName);
            $qb
                ->addSelect($qbField)
                ->orWhere($qb->expr()->isNotNull($qbField), $qb->expr()->neq($qbField, ':empty'))
                ->setParameter('empty', '', Types::STRING);
        }

        return $qb;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $fields An associative array of LFV association names and their fields that need sanitizing.
     *  [
     *      string $associationName => [ // Name of association to localized fallback values
     *          string $fieldName1, // Name of field for sanitizing inside localized fallback value
     *          string $fieldName2,
     *          // ...
     *      ],
     *      // ...
     *  ]
     */
    protected function sanitizeEntityRow(
        ClassMetadataInfo $classMetadata,
        array $row,
        array $fields,
        int $mode,
        array $modeArguments,
        bool $applyChanges
    ): array {
        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass($classMetadata->getName());

        $affectedAssociations = [];
        foreach ($fields as $associationName => $fieldNames) {
            if ($row['association'] !== $associationName) {
                continue;
            }

            $lfvClassMetadata = $entityManager->getClassMetadata(
                $classMetadata->getAssociationTargetClass($associationName)
            );

            $affectedFields = $this->sanitizeLocalizedFallbackValueForEntityRow(
                $lfvClassMetadata,
                $row['data'],
                $fieldNames,
                $mode,
                $modeArguments,
                $applyChanges
            );

            if ($affectedFields) {
                $affectedAssociations[] = $associationName;
            }
        }

        return $affectedAssociations;
    }

    /**
     * @param ClassMetadataInfo $lfvClassMetadata
     * @param array $row
     *  [
     *      'id' => int|string $id, // Id of the parent entity of localized fallback value
     *      'lfv_id' => int $lfvId, // Id of the localized fallback value that needs sanitizing
     *      'fieldName1' => string $fieldValue, // Field that needs sanitizing
     *      // ...
     *  ]
     * @param string[] $fieldNames Fields of localized fallback value that need sanitizing.
     * @param bool $applyChanges
     *
     * @return string[] Affected fields
     */
    private function sanitizeLocalizedFallbackValueForEntityRow(
        ClassMetadataInfo $lfvClassMetadata,
        array $row,
        array $fieldNames,
        int $mode,
        array $modeArguments,
        bool $applyChanges
    ): array {
        $lfvClassName = $lfvClassMetadata->getName();

        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass($lfvClassName);

        $updateQb = $entityManager->createQueryBuilder()->update($lfvClassName, 'lfv');
        $affectedFields = [];

        foreach ($fieldNames as $fieldName) {
            if (empty($row[$fieldName])) {
                // Skips if field is empty.
                continue;
            }

            $sanitizedValue = $this->sanitizeText((string)$row[$fieldName], $mode, $modeArguments);
            if ($sanitizedValue === $row[$fieldName]) {
                // Skips field if data is not changed after sanitizing.
                continue;
            }

            $affectedFields[] = $fieldName;

            $placeholder = QueryBuilderUtil::sprintf(':value_%s', $fieldName);
            $updateQb
                ->set(QueryBuilderUtil::sprintf('lfv.%s', $fieldName), $placeholder)
                ->setParameter($placeholder, $sanitizedValue, Types::TEXT);
        }

        if ($applyChanges && $affectedFields) {
            $updateQb
                ->where($updateQb->expr()->eq('lfv.id', ':lfv_id'))
                ->setParameter('lfv_id', $row['lfv_id'])
                ->getQuery()
                ->execute();
        }

        return $affectedFields;
    }
}
