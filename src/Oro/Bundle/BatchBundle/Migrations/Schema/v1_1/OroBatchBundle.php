<?php

namespace Oro\Bundle\BatchBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;

class OroBatchBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "ALTER TABLE oro_batch_job_execution RENAME TO akeneo_batch_job_execution;",
            "ALTER TABLE oro_batch_job_instance RENAME TO akeneo_batch_job_instance;",
            "ALTER TABLE oro_batch_mapping_field RENAME TO akeneo_batch_mapping_field;",
            "ALTER TABLE oro_batch_mapping_item RENAME TO akeneo_batch_mapping_item;",
            "ALTER TABLE oro_batch_step_execution RENAME TO akeneo_batch_step_execution;",
        ];
    }
}
