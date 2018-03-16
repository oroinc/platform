<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migration\UpdateTableFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MigrateRelations implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_segment',
            'entity',
            'OroCRM',
            'Oro'
        ));
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_segment',
            'definition',
            'OroCRM',
            'Oro'
        ));
    }
}
