<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroDataAuditBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $auditFieldTable = $schema->getTable('oro_audit_field');
        $auditFieldTable->getColumn('old_datetimetz')->setOptions(['comment' => '(DC2Type:datetimetz)']);
        $auditFieldTable->getColumn('new_datetimetz')->setOptions(['comment' => '(DC2Type:datetimetz)']);
    }
}
