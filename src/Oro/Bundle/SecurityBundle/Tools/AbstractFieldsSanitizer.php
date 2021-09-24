<?php

namespace Oro\Bundle\SecurityBundle\Tools;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

/**
 * Abstract class for entity fields sanitizers.
 */
abstract class AbstractFieldsSanitizer implements FieldsSanitizerInterface
{
    /**
     * Sanitization mode that strips all tags using {@see HtmlTagHelper::stripTags()} method.
     */
    public const MODE_STRIP_TAGS = 1 << 0;

    /**
     * Sanitization mode that uses {@see HtmlTagHelper::sanitize()} method.
     */
    public const MODE_SANITIZE = 1 << 2;

    protected ManagerRegistry $managerRegistry;

    protected HtmlTagHelper $htmlTagHelper;

    public function __construct(ManagerRegistry $managerRegistry, HtmlTagHelper $htmlTagHelper)
    {
        $this->managerRegistry = $managerRegistry;
        $this->htmlTagHelper = $htmlTagHelper;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $mode Sanitization mode, a MODE_* constant from {@see AbstractFieldsSanitizer}
     * @param array $modeArguments Extra arguments specific for the mode of the chosen sanitization method.
     */
    public function sanitizeByFieldType(
        string $entityClass,
        string $fieldTypeToSanitize,
        int $mode,
        array $modeArguments,
        bool $applyChanges,
        int $chunkSize = 1000
    ): iterable {
        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass($entityClass);
        if (!$entityManager) {
            throw new \InvalidArgumentException(
                sprintf('Entity manager for class %s was not found', $entityClass)
            );
        }

        $classMetadata = $entityManager->getClassMetadata($entityClass);
        $fields = $this->getFieldsToSanitize($classMetadata, $fieldTypeToSanitize);

        if ($fields) {
            $rowsToSanitize = $this
                ->getRowsToSanitize($classMetadata, $fields, $chunkSize);

            foreach ($rowsToSanitize as $row) {
                $affectedFields = $this
                    ->sanitizeEntityRow($classMetadata, $row, $fields, $mode, $modeArguments, $applyChanges);

                if ($affectedFields) {
                    yield $row['id'] => $affectedFields;
                }
            }
        }
    }

    /**
     * Returns names of fields to sanitize.
     *
     * @param ClassMetadataInfo $classMetadata
     * @param string $fieldTypeToSanitize
     *
     * @return array
     */
    abstract protected function getFieldsToSanitize(
        ClassMetadataInfo $classMetadata,
        string $fieldTypeToSanitize
    ): array;

    /**
     * Fetches entity rows to sanitize.
     *
     * @param ClassMetadataInfo $classMetadata
     * @param array $fields
     * @param int $chunkSize
     *
     * @return iterable<array>
     *  [
     *      'id' => int|string $entityId,
     *      'sampleField1' => string $fieldValue,
     *      // ...
     *  ]
     */
    protected function getRowsToSanitize(
        ClassMetadataInfo $classMetadata,
        array $fields,
        int $chunkSize
    ): iterable {
        $qb = $this->getRowsToSanitizeQueryBuilder($classMetadata, $fields);

        return $this->getIterable($qb->getQuery(), $chunkSize);
    }

    /**
     * Returns the query that fetches entity rows to sanitize.
     *
     * @param ClassMetadataInfo $classMetadata
     * @param array $fields
     *
     * @return QueryBuilder
     */
    abstract protected function getRowsToSanitizeQueryBuilder(
        ClassMetadataInfo $classMetadata,
        array $fields
    ): QueryBuilder;

    /**
     * @param ClassMetadataInfo $classMetadata
     * @param array $row
     * @param array $fields
     * @param bool $applyChanges If true, persist sanitized data. Otherwise method returns entities ids and
     *                           their fields that should be sanitized.
     * @param int $mode Sanitization mode, a MODE_* constant from {@see AbstractFieldsSanitizer}
     * @param array $modeArguments Extra arguments specific for the mode of the chosen sanitization method.
     *
     * @return string[] Affected fields.
     */
    abstract protected function sanitizeEntityRow(
        ClassMetadataInfo $classMetadata,
        array $row,
        array $fields,
        int $mode,
        array $modeArguments,
        bool $applyChanges
    ): array;

    protected function getIterable(Query $query, int $chunkSize): iterable
    {
        $query->setMaxResults($chunkSize);
        $offset = 0;
        do {
            $query->setFirstResult($offset);

            $row = null;
            $rows = $query->getScalarResult();
            foreach ($rows as $row) {
                $offset++;

                yield $row;
            }
        } while ($row !== null);
    }

    /**
     * @param string $text
     * @param int $mode Sanitization mode, a MODE_* constant from {@see AbstractFieldsSanitizer}
     * @param array $modeArguments Extra arguments specific for the mode of the chosen sanitization method.
     *
     * @return string
     */
    protected function sanitizeText(string $text, int $mode, array $modeArguments): string
    {
        switch ($mode) {
            case self::MODE_STRIP_TAGS:
                $text = $this->htmlTagHelper->stripTags($text, ...$modeArguments);
                break;

            case self::MODE_SANITIZE:
                $text = (string)$this->htmlTagHelper->sanitize($text, ...$modeArguments);
                break;

            default:
                throw new \InvalidArgumentException(
                    sprintf('Invalid mode %d, expected one of %s', $mode, implode(', ', $this->getAllowedModes()))
                );
        }

        return $text;
    }

    /**
     * @return int[]
     */
    protected function getAllowedModes(): array
    {
        return [self::MODE_STRIP_TAGS, self::MODE_SANITIZE];
    }
}
