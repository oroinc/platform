<?php

namespace Oro\Component\Testing\Unit\ORM\Mocks;

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

    // phpcs:disable
    #[\Override]
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        // phpcs:enable
    }
}
