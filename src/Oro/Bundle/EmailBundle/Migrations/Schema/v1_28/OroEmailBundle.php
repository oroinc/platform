<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_28;

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
        static::oroEmailFolderChangeColumn($schema);
    }

    /**
     * @param Schema $schema
     */
    public static function oroEmailFolderChangeColumn(Schema $schema)
    {
        $table = $schema->getTable('oro_email_folder');
        if ($table->hasColumn('failed_count')) {
            $table->getColumn('failed_count')->setDefault("0");
        }
    }
}
