<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds field `external_url` to `oro_attachment_file` table.
 */
class AddExternalUrl implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addExternalUrlToAttachmentFileTable($schema);
    }

    private function addExternalUrlToAttachmentFileTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_attachment_file');
        $table->addColumn('external_url', 'string', ['length' => 1024, 'notnull' => false]);
    }
}
