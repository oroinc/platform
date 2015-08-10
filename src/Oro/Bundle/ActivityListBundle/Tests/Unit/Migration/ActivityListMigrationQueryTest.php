<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityListBundle\Migration\ActivityListMigrationQuery;
use Oro\Bundle\ActivityListBundle\Migration\Extension\ActivityListExtension;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;

class ActivityListMigrationQueryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActivityListMigrationQuery */
    protected $migrationQuery;

    /** @var Schema */
    protected $schema;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $provider;

    /** @var ActivityListExtension */
    protected $activityListExtension;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataHelper;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    public function setUp()
    {
        $this->schema                = new Schema();
        $this->activityListExtension = new ActivityListExtension();
        $this->nameGenerator         = new ExtendDbIdentifierNameGenerator();

        $this->provider = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataHelper = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataHelper->expects($this->any())
            ->method('getEntityClassByTableName')
            ->willReturnCallback(
                function ($tableName) {
                    if ($tableName === 'acme_test') {
                        return 'Acme\TestBundle\Entity\Test';
                    }

                    return 'Oro\Bundle\ActivityListBundle\Entity\ActivityList';
                }
            );

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->migrationQuery = new ActivityListMigrationQuery(
            $this->schema,
            $this->provider,
            $this->activityListExtension,
            $this->metadataHelper,
            $this->nameGenerator,
            $this->configManager
        );
    }

    public function testRunActivityLists()
    {
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue(new MySqlPlatform()));
        $this->migrationQuery->setConnection($connection);

        $table = $this->schema->createTable('acme_test');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);

        $table = $this->schema->createTable('oro_activity_list');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);

        $extendOptionsManager = new ExtendOptionsManager();
        $entityMetadataHelper = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $extendExtension      = new ExtendExtension($extendOptionsManager, $entityMetadataHelper);
        $extendExtension->setNameGenerator($this->nameGenerator);
        $this->activityListExtension->setExtendExtension($extendExtension);

        $this->provider->expects($this->once())
            ->method('getTargetEntityClasses')
            ->willReturn(['Acme\TestBundle\Entity\Test']);
        $this->metadataHelper->expects($this->once())
            ->method('getTableNameByEntityClass')
            ->with('Acme\TestBundle\Entity\Test')
            ->willReturn('acme_test');

        $entityMetadataHelper->expects($this->any())
            ->method('getEntityClassByTableName')
            ->willReturnCallback(
                function ($tableName) {
                    if ($tableName === 'acme_test') {
                        return 'Acme\TestBundle\Entity\Test';
                    }

                    return 'Oro\Bundle\ActivityListBundle\Entity\ActivityList';
                }
            );

        $log = $this->migrationQuery->getDescription();
        $this->assertEquals(
            'CREATE TABLE oro_rel_c3990ba6784dd132527c89 (activitylist_id INT NOT NULL, test_id INT NOT NULL, '
            . 'INDEX IDX_53682E3596EB1108 (activitylist_id), INDEX IDX_53682E351E5D0459 (test_id), '
            . 'PRIMARY KEY(activitylist_id, test_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci '
            . 'ENGINE = InnoDB',
            $log[0]
        );
        $this->assertEquals(
            'ALTER TABLE oro_rel_c3990ba6784dd132527c89 ADD CONSTRAINT FK_53682E3596EB1108 '
            . 'FOREIGN KEY (activitylist_id) REFERENCES oro_activity_list (id) ON DELETE CASCADE',
            $log[1]
        );
        $this->assertEquals(
            'ALTER TABLE oro_rel_c3990ba6784dd132527c89 ADD CONSTRAINT FK_53682E351E5D0459 FOREIGN KEY (test_id) '
            . 'REFERENCES acme_test (id) ON DELETE CASCADE',
            $log[2]
        );
    }
}
