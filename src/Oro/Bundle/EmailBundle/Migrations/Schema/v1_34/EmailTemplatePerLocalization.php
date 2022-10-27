<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_34;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class EmailTemplatePerLocalization implements Migration, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('oro_email_template_localized')) {
            self::oroEmailTemplateLocalizedTable($schema);

            if ($schema->hasTable('oro_email_template_translation')) {
                $queries->addPostQuery(new MigrateEmailTemplatesQuery($schema));
            }
        }

        self::oroEmailTemplateLocalizedForeignKeys($schema);

        if ($schema->hasTable('oro_email_template_translation')) {
            $toSchema = clone $schema;
            $toSchema->dropTable('oro_email_template_translation');

            foreach ($schema->getMigrateToSql($toSchema, $this->platform) as $query) {
                $queries->addQuery($query);
            }

            $queries->addPostQuery(new RemoveFieldQuery(EmailTemplate::class, 'translations'));
        }
    }

    public static function oroEmailTemplateLocalizedTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_template_localized');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('template_id', 'integer', ['notnull' => true]);
        $table->addColumn('localization_id', 'integer', ['notnull' => true]);
        $table->addColumn('subject', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('subject_fallback', 'boolean', ['notnull' => true, 'default' => true]);
        $table->addColumn('content', 'text', ['notnull' => false]);
        $table->addColumn('content_fallback', 'boolean', ['notnull' => true, 'default' => true]);
        $table->setPrimaryKey(['id']);
    }

    public static function oroEmailTemplateLocalizedForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_template_localized');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_template'),
            ['template_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        if ($schema->hasTable('oro_localization')) {
            $table = $schema->getTable('oro_email_template_localized');
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_localization'),
                ['localization_id'],
                ['id'],
                ['onDelete' => 'CASCADE']
            );
        }
    }
}
