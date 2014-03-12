<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigration;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigDumper;

use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateEntityConfigMigrationTest extends \PHPUnit_Framework_TestCase
{
    public function testUp()
    {
        /** @var ConfigManager $cm */
        $cm = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var UpdateEntityConfigMigration $postUpMigrationListener */
        $migration = new UpdateEntityConfigMigration(
            new ConfigDumper($cm)
        );

        $table = new Table('table1');
        $table->addColumn('id', 'integer');
        $table->setPrimaryKey(['id']);

        /** @var Schema $schema */
        $schema  = new Schema([$table], [], null);
        $queries = new QueryBag();

        $queries->addQuery('ALTER TABLE table1 ADD field1 INT DEFAULT NULL');
        $queries->addQuery('CREATE INDEX IDX_7166D3717272F2F0 ON table1 (field1)');

        $this->assertCount(0, $queries->getPreQueries());
        $this->assertCount(2, $queries->getPostQueries());

        $migration->up($schema, $queries);

        $this->assertCount(0, $queries->getPreQueries());
        $this->assertCount(3, $queries->getPostQueries());
        $this->assertInstanceOf(
            'Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigrationQuery',
            $queries->getPostQueries()[2]
        );
    }
}
