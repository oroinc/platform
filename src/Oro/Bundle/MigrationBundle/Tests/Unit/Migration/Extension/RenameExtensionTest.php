<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Extension;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SQLServer2005Platform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider renameTableProvider
     */
    public function testRenameTable($platform, $expectedSql)
    {
        $extension = new RenameExtension();
        $extension->setDatabasePlatform($platform);

        $schema = new Schema([new Table('old_table')]);
        $queries = new QueryBag();

        $extension->renameTable($schema, $queries, 'old_table', 'new_table');
        /** @var MigrationQuery $query */
        $query = $queries->getPostQueries()[0];

        $this->assertEquals($expectedSql, $query->getDescription());
    }

    public function renameTableProvider()
    {
        return [
            [new MySqlPlatform(), 'ALTER TABLE old_table RENAME TO new_table'],
            [new PostgreSqlPlatform(), 'ALTER TABLE old_table RENAME TO new_table'],
            [new OraclePlatform(), 'ALTER TABLE old_table RENAME TO new_table'],
            [
                new SQLServer2005Platform(),
                [
                    "sp_RENAME 'old_table', 'new_table'",
                    "DECLARE @sql NVARCHAR(MAX) = N''; "
                    . "SELECT @sql += N'EXEC sp_rename N''' + dc.name + ''', N'''"
                    . " + REPLACE(dc.name, '50BD45A0', 'EBFCC9B') + ''', ''OBJECT'';' "
                    . "FROM sys.default_constraints dc JOIN sys.tables tbl ON dc.parent_object_id = tbl.object_id "
                    . "WHERE tbl.name = 'new_table';"
                    . "EXEC sp_executesql @sql"
                ]
            ],
        ];
    }
}
