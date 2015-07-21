<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        static::oroEmailAutoResponseRuleTable($schema);
        static::oroEmailAutoResponseRuleConditionTable($schema);
        static::oroEmailTemplateTable($schema);
    }

    /**
     * @param Schema $schema
     */
    public static function oroEmailAutoResponseRuleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_auto_response_rule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('template_id', 'integer', ['notnull' => false]);
        $table->addColumn('mailbox_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('active', 'boolean', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['template_id'], 'IDX_58CB592A5DA0FB8', []);
        $table->addIndex(['mailbox_id'], 'IDX_58CB592A66EC35CC', []);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_template'),
            ['template_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_mailbox'),
            ['mailbox_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    public static function oroEmailAutoResponseRuleConditionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_response_rule_cond');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('rule_id', 'integer', ['notnull' => false]);
        $table->addColumn('operation', 'string', ['length' => 5]);
        $table->addColumn('field', 'string', ['length' => 255]);
        $table->addColumn('filterType', 'string', ['length' => 255]);
        $table->addColumn('filterValue', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('position', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['rule_id'], 'IDX_4132B1DB744E0351', []);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_auto_response_rule'),
            ['rule_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    public static function oroEmailTemplateTable(Schema $schema)
    {
       $table = $schema->getTable('oro_email_template');
       $table->addColumn('visible', 'boolean', ['default' => '1']);
    }
}
