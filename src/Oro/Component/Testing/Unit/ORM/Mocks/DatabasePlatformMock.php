<?php

namespace Oro\Component\Testing\Unit\ORM\Mocks;

use Doctrine\DBAL\DBALException;
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
    private $prefersSequences = false;

    /**
     * @override
     */
    public function getNativeDeclaration(array $field)
    {
    }

    /**
     * @override
     */
    public function getPortableDeclaration(array $field)
    {
    }

    /**
     * @override
     */
    public function prefersIdentityColumns()
    {
        return $this->prefersIdentityColumns;
    }

    /**
     * @override
     */
    public function prefersSequences()
    {
        return $this->prefersSequences;
    }

    /** @override */
    public function getSequenceNextValSQL($sequenceName)
    {
        return $this->sequenceNextValSql;
    }

    /** @override */
    public function getBooleanTypeDeclarationSQL(array $field)
    {
    }

    /** @override */
    public function getIntegerTypeDeclarationSQL(array $field)
    {
    }

    /** @override */
    public function getBigIntTypeDeclarationSQL(array $field)
    {
    }

    /** @override */
    public function getSmallIntTypeDeclarationSQL(array $field)
    {
    }

    /** @override */
    // @codingStandardsIgnoreStart
    protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef)
    {
        // @codingStandardsIgnoreEnd
    }

    /** @override */
    public function getVarcharTypeDeclarationSQL(array $field)
    {
    }

    /** @override */
    public function getClobTypeDeclarationSQL(array $field)
    {
    }

    /* MOCK API */

    public function setPrefersIdentityColumns($bool)
    {
        $this->_prefersIdentityColumns = $bool;
    }

    public function setPrefersSequences($bool)
    {
        $this->_prefersSequences = $bool;
    }

    public function setSequenceNextValSql($sql)
    {
        $this->_sequenceNextValSql = $sql;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    protected function initializeDoctrineTypeMappings()
    {
    }

    /**
     * Gets the SQL Snippet used to declare a BLOB column type.
     */
    public function getBlobTypeDeclarationSQL(array $field)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    public function supportsSequences(): bool
    {
        return true;
    }

    public function supportsIdentityColumns(): bool
    {
        return $this->prefersIdentityColumns;
    }
}
