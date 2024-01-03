<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_24;

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
        $schema->getTable('oro_email_recipient')
            ->dropIndex('IDX_7DAF9656A832C1C9');
    }
}
