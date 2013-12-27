<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks;

use Doctrine\DBAL\DBALException;

/**
 * This class is a clone of namespace Doctrine\Tests\Mocks\DatabasePlatformMock that is excluded from doctrine
 * package since v2.4.
 */
class DatabasePlatformMock extends \Doctrine\DBAL\Platforms\AbstractPlatform
{
    private $sequenceNextValSql = "";
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
    // @codingStandardsIgnoreEnd
    {

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
        return 'mock';
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
}
