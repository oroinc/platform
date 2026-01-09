<?php

namespace Oro\Component\Testing\Unit\ORM\Mocks;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * This class is a clone of namespace Doctrine\Tests\Mocks\DatabasePlatformMock that is excluded from doctrine
 * package since v2.4.
 */
class DatabasePlatformMock extends AbstractPlatform
{
    private $name = 'mock';
    private $sequenceNextValSql = '';
    private $prefersIdentityColumns = true;
    private $reservedKeywordsClass = null;

    /**
     * @override
     */
    #[\Override]
    public function prefersIdentityColumns()
    {
        return $this->prefersIdentityColumns;
    }

    /** @override */
    #[\Override]
    public function getSequenceNextValSQL($sequence)
    {
        return $this->sequenceNextValSql;
    }

    /** @override */
    #[\Override]
    public function getBooleanTypeDeclarationSQL(array $column)
    {
    }

    /** @override */
    #[\Override]
    public function getIntegerTypeDeclarationSQL(array $column)
    {
    }

    /** @override */
    #[\Override]
    public function getBigIntTypeDeclarationSQL(array $column)
    {
    }

    /** @override */
    #[\Override]
    public function getSmallIntTypeDeclarationSQL(array $column)
    {
    }

    /** @override */
    // phpcs:disable
    #[\Override]
    protected function _getCommonIntegerTypeDeclarationSQL(array $column)
    {
        // phpcs:enable
    }

    /** @override */
    #[\Override]
    public function getVarcharTypeDeclarationSQL(array $column)
    {
    }

    /** @override */
    #[\Override]
    public function getClobTypeDeclarationSQL(array $column)
    {
    }

    /* MOCK API */

    public function setPrefersIdentityColumns($bool)
    {
        $this->prefersIdentityColumns = $bool;
    }


    public function setSequenceNextValSql($sql)
    {
        $this->sequenceNextValSql = $sql;
    }

    #[\Override]
    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    #[\Override]
    protected function initializeDoctrineTypeMappings()
    {
    }

    /**
     * Gets the SQL Snippet used to declare a BLOB column type.
     */
    #[\Override]
    public function getBlobTypeDeclarationSQL(array $column)
    {
        throw Exception::notSupported(__METHOD__);
    }

    #[\Override]
    public function supportsSequences(): bool
    {
        return true;
    }

    #[\Override]
    public function supportsIdentityColumns(): bool
    {
        return $this->prefersIdentityColumns;
    }

    #[\Override]
    protected function getReservedKeywordsClass()
    {
        return $this->reservedKeywordsClass ?? parent::getReservedKeywordsClass();
    }

    public function setReservedKeywordsClass(?string $reservedKeywordsClass): void
    {
        $this->reservedKeywordsClass = $reservedKeywordsClass;
    }

    public function getCurrentDatabaseExpression(): string
    {
        return 'CURRENT_DATABASE()';
    }
}
