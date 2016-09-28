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
        self::addTextBodyFieldToEmailBodyTable($schema);
        $queries->addPostQuery(new UpdateBodyQuery());
    }

    /**
     * @param Schema $schema
     */
    public static function addTextBodyFieldToEmailBodyTable(Schema $schema)
    {
        $table = $schema->getTable('oro_email_body');
        if (!$table->hasColumn('text_body')) {
            $table->addColumn('text_body', 'text', []);
        }
    }
}
