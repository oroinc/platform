<?php

namespace Oro\Bundle\BatchBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroBatchBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addSql(
            $queries->getRenameTableSql('oro_batch_job_execution', 'akeneo_batch_job_execution')
        );
        $queries->addSql(
            $queries->getRenameTableSql('oro_batch_job_instance', 'akeneo_batch_job_instance')
        );
        $queries->addSql(
            $queries->getRenameTableSql('oro_batch_mapping_field', 'akeneo_batch_mapping_field')
        );
        $queries->addSql(
            $queries->getRenameTableSql('oro_batch_mapping_item', 'akeneo_batch_mapping_item')
        );
        $queries->addSql(
            $queries->getRenameTableSql('oro_batch_step_execution', 'akeneo_batch_step_execution')
        );
    }
}
