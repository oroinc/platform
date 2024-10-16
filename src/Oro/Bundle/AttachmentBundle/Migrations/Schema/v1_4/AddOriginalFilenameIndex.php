<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddOriginalFilenameIndex implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $schema->getTable('oro_attachment_file')
            ->addIndex(['original_filename'], 'att_file_orig_filename_idx');
    }
}
