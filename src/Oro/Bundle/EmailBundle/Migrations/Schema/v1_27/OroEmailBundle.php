<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_27;

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
        static::oroEmailFolderTable($schema);
        self::addTextBodyFieldToEmailBodyTable($schema);
        $queries->addPostQuery(new UpdateBodyQuery());
    }

    /**
     * @param Schema $schema
     */
    public static function oroEmailFolderTable(Schema $schema)
    {
        $table = $schema->getTable('oro_email_folder');
        if (!$table->hasColumn('failed_count')) {
            $table->addColumn('failed_count', 'integer', ['notnull' => true]);
    public static function addTextBodyFieldToEmailBodyTable(Schema $schema)
    {
        $table = $schema->getTable('oro_email_body');
        if (!$table->hasColumn('text_body')) {
            $table->addColumn('text_body', 'text', []);
        }
    }
}
