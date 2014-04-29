<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration, DatabasePlatformAwareInterface, OrderedMigrationInterface
{
    /** @var AbstractPlatform */
    protected $platform;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // update data in accordance with new column requirement
        $queries->addPreQuery(
            sprintf(
                "UPDATE oro_email SET message_id = CONCAT('id.', REPLACE(%s, '-',''), '%s') WHERE message_id IS NULL",
                $this->platform->getGuidExpression(),
                '@bap.migration.generated'
            )
        );

        self::oroEmailToFolderRelationTable($schema);

        // make message_id not null & add index
        $table = $schema->getTable('oro_email');
        $table->changeColumn('message_id', ['notnull' => true]);
        $table->addIndex(['message_id'], 'IDX_email_message_id', []);

        // migrate existing email-folder relations
        $queries->addPostQuery(
            "INSERT INTO oro_email_to_folder (email_id, emailfolder_id) SELECT id, folder_id FROM oro_email"
        );
    }

    /**
     * Create many-to-many relation table
     *
     * @param Schema $schema
     */
    public static function oroEmailToFolderRelationTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_to_folder');
        $table->addColumn('email_id', 'integer', []);
        $table->addColumn('emailfolder_id', 'integer', []);
        $table->addIndex(['email_id'], 'oro_folder_email_idx', []);
        $table->addIndex(['emailfolder_id'], 'oro_email_folder_idx', []);

        $table->setPrimaryKey(['email_id', 'emailfolder_id']);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email'),
            ['email_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder'),
            ['emailfolder_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Sets the database platform
     *
     * @param AbstractPlatform $platform
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }
}
