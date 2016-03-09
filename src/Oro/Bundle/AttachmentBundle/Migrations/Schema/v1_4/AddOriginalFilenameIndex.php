<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddOriginalFilenameIndex implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addOriginalFilenameIndex($schema);
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function addOriginalFilenameIndex(Schema $schema)
    {
        // Adding these index due to issue with mysql 5.6 version @see CRM-5117
        $table = $schema->getTable('oro_attachment_file');
        $table->addIndex(['original_filename'], 'att_file_orig_filename_idx');
    }
}
