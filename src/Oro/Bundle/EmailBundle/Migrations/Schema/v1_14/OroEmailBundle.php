<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $schema->getTable('oro_email_attachment')
            ->addColumn('embedded_content_id', 'string', ['length' => 255, 'notnull' => false]);
    }
}
