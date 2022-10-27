<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddFileUuidColumn implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addUuidColumn($schema);
    }

    public static function addUuidColumn(Schema $schema): void
    {
        $table = $schema->getTable('oro_attachment_file');
        if ($table->hasColumn('uuid')) {
            return;
        }

        $table->addColumn('uuid', 'guid', ['notnull' => false]);
        $table->addIndex(['uuid'], 'att_file_uuid_idx', []);
    }
}
