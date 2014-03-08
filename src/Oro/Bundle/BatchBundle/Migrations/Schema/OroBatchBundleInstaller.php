<?php

namespace Oro\Bundle\BatchBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\BatchBundle\Migrations\Schema\v1_0\OroBatchBundle;

class OroBatchBundleInstaller extends Installation
{
    /**
     * @inheritdoc
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        OroBatchBundle::oroBatchJobExecutionTable($schema, 'akeneo_batch_job_execution');
        OroBatchBundle::oroBatchJobInstanceTable($schema, 'akeneo_batch_job_instance');
        OroBatchBundle::oroBatchMappingFieldTable($schema, 'akeneo_batch_mapping_field');
        OroBatchBundle::oroBatchMappingItemTable($schema, 'akeneo_batch_mapping_item');
        OroBatchBundle::oroBatchStepExecutionTable($schema, 'akeneo_batch_step_execution');

        OroBatchBundle::oroBatchJobExecutionForeignKeys(
            $schema,
            'akeneo_batch_job_execution',
            'akeneo_batch_job_instance'
        );
        OroBatchBundle::oroBatchMappingFieldForeignKeys(
            $schema,
            'akeneo_batch_mapping_field',
            'akeneo_batch_mapping_item'
        );
        OroBatchBundle::oroBatchStepExecutionForeignKeys(
            $schema,
            'akeneo_batch_step_execution',
            'akeneo_batch_job_execution'
        );
    }
}
