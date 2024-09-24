<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddColumn implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function getOrder()
    {
        return 10;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $auditTable = $schema->getTable('oro_audit');
        $auditTable->addColumn('type', 'string', ['length' => 255, 'notnull' => false]);
    }
}
