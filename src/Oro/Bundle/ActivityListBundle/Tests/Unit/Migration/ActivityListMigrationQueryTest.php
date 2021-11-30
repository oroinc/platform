<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Migration\ActivityListMigrationQuery;
use Oro\Bundle\ActivityListBundle\Migration\Extension\ActivityListExtension;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigBag;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\EntityConfig\Mock\ConfigurationHandlerMock;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;

class ActivityListMigrationQueryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ActivityListMigrationQuery */
    private $migrationQuery;

    /** @var Schema */
    private $schema;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var ActivityListExtension */
    private $activityListExtension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $metadataHelper;

    /** @var ExtendDbIdentifierNameGenerator */
    private $nameGenerator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    protected function setUp(): void
    {
        $this->schema = new Schema();
        $this->activityListExtension = new ActivityListExtension();
        $this->nameGenerator = new ExtendDbIdentifierNameGenerator();

        $this->provider = $this->createMock(ActivityListChainProvider::class);
        $this->metadataHelper = $this->createMock(EntityMetadataHelper::class);

        $this->metadataHelper->expects($this->any())
            ->method('getEntityClassesByTableName')
            ->willReturnCallback(
                function ($tableName) {
                    if ($tableName === 'acme_test') {
                        return ['Acme\TestBundle\Entity\Test'];
                    }

                    return [ActivityList::class];
                }
            );

        $this->configManager = $this->createMock(ConfigManager::class);

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
        $connection = $this->createMock(Connection::class);

        $connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());
        $this->migrationQuery->setConnection($connection);

        $table = $this->schema->createTable('acme_test');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);

        $table = $this->schema->createTable('oro_activity_list');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);

        $extendOptionsManager = new ExtendOptionsManager(ConfigurationHandlerMock::getInstance());
        $entityMetadataHelper = $this->createMock(EntityMetadataHelper::class);
        $extendExtension = new ExtendExtension($extendOptionsManager, $entityMetadataHelper, new PropertyConfigBag([]));
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
            ->method('getEntityClassesByTableName')
            ->willReturnCallback(
                function ($tableName) {
                    if ($tableName === 'acme_test') {
                        return ['Acme\TestBundle\Entity\Test'];
                    }

                    return [ActivityList::class];
                }
            );

        $log = $this->migrationQuery->getDescription();
        $this->assertEquals(
            'CREATE TABLE oro_rel_c3990ba6784dd132527c89 (activitylist_id INT NOT NULL, test_id INT NOT NULL, '
            . 'INDEX IDX_53682E3596EB1108 (activitylist_id), INDEX IDX_53682E351E5D0459 (test_id), '
            . 'PRIMARY KEY(activitylist_id, test_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` '
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
