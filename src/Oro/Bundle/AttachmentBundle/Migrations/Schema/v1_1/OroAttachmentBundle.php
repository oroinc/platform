<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroAttachmentBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        //$schema->getTable('oro_attachment')->addColumn('comment', 'string', ['length' => 255, 'notnull' => false]);
    }
}
