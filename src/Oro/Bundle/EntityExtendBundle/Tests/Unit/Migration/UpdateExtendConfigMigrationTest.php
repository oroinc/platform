<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

use Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigration;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;

use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateExtendConfigMigrationTest extends \PHPUnit_Framework_TestCase
{
    public function testUp()
    {
        /** @var ConfigManager $cm */
        $cm = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var OroEntityManager $em */
        $em = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\OroEntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ExtendOptionsManager $om */
        $om = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager')
            ->disableOriginalConstructor()
            ->getMock();
        $om
            ->expects($this->once())
            ->method('getExtendOptions')
            ->will($this->returnValue([]));

        $nameGenerator = new ExtendDbIdentifierNameGenerator();

        /** @var UpdateExtendConfigMigration $postUpMigrationListener */
        $migration = new UpdateExtendConfigMigration(
            new ExtendConfigProcessor($cm),
            new ExtendConfigDumper($em, $nameGenerator, '')
        );

        $table = new Table('table1');
        $table->addColumn('id', 'integer');
        $table->setPrimaryKey(['id']);

        /** @var ExtendSchema $schema */
        $schema  = new ExtendSchema($om, $nameGenerator, [$table], [], null);
        $queries = new QueryBag();

        $queries->addQuery('ALTER TABLE table1 ADD field1 INT DEFAULT NULL');
        $queries->addQuery('CREATE INDEX IDX_7166D3717272F2F0 ON table1 (field1)');

        $this->assertCount(0, $queries->getPreQueries());
        $this->assertCount(2, $queries->getPostQueries());

        $this->assertEquals(
            [],
            $migration->up($schema, $queries)
        );

        $this->assertCount(0, $queries->getPreQueries());
        $this->assertCount(3, $queries->getPostQueries());
        $this->assertInstanceOf(
            'Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigrationQuery',
            $queries->getPostQueries()[2]
        );
    }
}
