<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Query\ResultSetMapping;

class PlatformResultSetMapping extends ResultSetMapping
{
    /** @var AbstractPlatform */
    protected $platform;

    public function __construct(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    #[\Override]
    public function setDiscriminatorColumn($alias, $discrColumn)
    {
        return parent::setDiscriminatorColumn(
            $alias,
            $this->platform->getSQLResultCasing($discrColumn)
        );
    }

    #[\Override]
    public function isFieldResult($columnName)
    {
        return parent::isFieldResult($this->platform->getSQLResultCasing($columnName));
    }

    #[\Override]
    public function addFieldResult($alias, $columnName, $fieldName, $declaringClass = null)
    {
        return parent::addFieldResult(
            $alias,
            $this->platform->getSQLResultCasing($columnName),
            $fieldName,
            $declaringClass
        );
    }

    #[\Override]
    public function addScalarResult($columnName, $alias, $type = 'string')
    {
        return parent::addScalarResult(
            $this->platform->getSQLResultCasing($columnName),
            $alias,
            $type
        );
    }

    #[\Override]
    public function isScalarResult($columnName)
    {
        return parent::isScalarResult($this->platform->getSQLResultCasing($columnName));
    }

    #[\Override]
    public function getScalarAlias($columnName)
    {
        return parent::getScalarAlias($this->platform->getSQLResultCasing($columnName));
    }

    #[\Override]
    public function getDeclaringClass($columnName)
    {
        return parent::getDeclaringClass($this->platform->getSQLResultCasing($columnName));
    }

    #[\Override]
    public function getEntityAlias($columnName)
    {
        return parent::getEntityAlias($this->platform->getSQLResultCasing($columnName));
    }

    #[\Override]
    public function getFieldName($columnName)
    {
        return parent::getFieldName($this->platform->getSQLResultCasing($columnName));
    }

    #[\Override]
    public function addMetaResult($alias, $columnName, $fieldName, $isIdentifierColumn = false, $type = null)
    {
        return parent::addMetaResult(
            $alias,
            $this->platform->getSQLResultCasing($columnName),
            $fieldName,
            $isIdentifierColumn,
            $type
        );
    }
}
