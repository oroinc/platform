<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks;

/**
 * This class is a clone of namespace Doctrine\Tests\Mocks\SchemaManagerMock that is excluded from doctrine
 * package since v2.4.
 */
class SchemaManagerMock extends \Doctrine\DBAL\Schema\AbstractSchemaManager
{
    public function __construct(\Doctrine\DBAL\Connection $conn)
    {
        parent::__construct($conn);
    }

    // @codingStandardsIgnoreStart
    protected function _getPortableTableColumnDefinition($tableColumn)
    // @codingStandardsIgnoreEnd
    {

    }
}
