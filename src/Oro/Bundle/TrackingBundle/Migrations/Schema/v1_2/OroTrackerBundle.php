<?php

namespace Oro\Bundle\TrackingBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\EntityBundle\Migrations\MigrateTypesQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTrackerBundle implements Migration, RenameExtensionAwareInterface, DatabasePlatformAwareInterface
{
    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** placed here to load in last order */
        $queries->addQuery(
            new MigrateTypesQuery($this->platform, $schema, 'oro_integration_channel', 'id', Type::INTEGER)
        );
        $queries->addQuery(
            new MigrateTypesQuery($this->platform, $schema, 'oro_integration_channel_status', 'id', Type::INTEGER)
        );
        $queries->addQuery(
            new MigrateTypesQuery($this->platform, $schema, 'oro_integration_transport', 'id', Type::INTEGER)
        );

        $queries->addQuery(new MigrateTypesQuery($this->platform, $schema, 'oro_access_group', 'id', Type::INTEGER));
        $queries->addQuery(new MigrateTypesQuery($this->platform, $schema, 'oro_access_role', 'id', Type::INTEGER));
        $queries->addQuery(new MigrateTypesQuery($this->platform, $schema, 'oro_user_email', 'id', Type::INTEGER));
        $queries->addQuery(new MigrateTypesQuery($this->platform, $schema, 'oro_user_status', 'id', Type::INTEGER));

        $table = $schema->getTable('oro_tracking_event');
        $table->changeColumn('value', ['type' => Type::getType('integer'), 'notnull' => true]);

        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'user',
            'user_identifier'
        );
    }
}
