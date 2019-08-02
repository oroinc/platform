<?php

namespace Oro\Bundle\ImportExportBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroImportExportBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_import_export_result');
        if (!$table->hasColumn('options')) {
            $table->addColumn('options', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        }
    }
}
