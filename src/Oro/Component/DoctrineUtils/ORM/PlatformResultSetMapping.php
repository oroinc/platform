<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * Normalizes column names in ORM result set mappings based on the target database platform.
 */
class PlatformResultSetMapping extends ResultSetMapping
{
    /** @var AbstractPlatform */
    protected $platform;

    public function __construct(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * Normalizes the column name based on the database platform.
     * PostgreSQL folds unquoted identifiers to lowercase.
     * Oracle folds unquoted identifiers to uppercase.
     * MySQL preserves the original case.
     */
    protected function normalizeColumnName(string $columnName): string
    {
        if ($this->platform instanceof PostgreSQLPlatform) {
            return strtolower($columnName);
        }

        if ($this->platform instanceof OraclePlatform) {
            return strtoupper($columnName);
        }

        return $columnName;
    }

    #[\Override]
    public function setDiscriminatorColumn($alias, $discrColumn)
    {
        return parent::setDiscriminatorColumn(
            $alias,
            $this->normalizeColumnName($discrColumn)
        );
    }

    #[\Override]
    public function isFieldResult($columnName)
    {
        return parent::isFieldResult($this->normalizeColumnName($columnName));
    }

    #[\Override]
    public function addFieldResult($alias, $columnName, $fieldName, $declaringClass = null)
    {
        return parent::addFieldResult(
            $alias,
            $this->normalizeColumnName($columnName),
            $fieldName,
            $declaringClass
        );
    }

    #[\Override]
    public function addScalarResult($columnName, $alias, $type = 'string')
    {
        return parent::addScalarResult(
            $this->normalizeColumnName($columnName),
            $alias,
            $type
        );
    }

    #[\Override]
    public function isScalarResult($columnName)
    {
        return parent::isScalarResult($this->normalizeColumnName($columnName));
    }

    #[\Override]
    public function getScalarAlias($columnName)
    {
        return parent::getScalarAlias($this->normalizeColumnName($columnName));
    }

    #[\Override]
    public function getDeclaringClass($columnName)
    {
        return parent::getDeclaringClass($this->normalizeColumnName($columnName));
    }

    #[\Override]
    public function getEntityAlias($columnName)
    {
        return parent::getEntityAlias($this->normalizeColumnName($columnName));
    }

    #[\Override]
    public function getFieldName($columnName)
    {
        return parent::getFieldName($this->normalizeColumnName($columnName));
    }

    #[\Override]
    public function addMetaResult($alias, $columnName, $fieldName, $isIdentifierColumn = false, $type = null)
    {
        return parent::addMetaResult(
            $alias,
            $this->normalizeColumnName($columnName),
            $fieldName,
            $isIdentifierColumn,
            $type
        );
    }
}
