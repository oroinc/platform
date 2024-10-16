<?php

namespace Oro\Bundle\BatchBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroBatchBundle implements Migration, RenameExtensionAwareInterface
{
    use RenameExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_batch_job_execution',
            'akeneo_batch_job_execution'
        );
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_batch_job_instance',
            'akeneo_batch_job_instance'
        );
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_batch_mapping_field',
            'akeneo_batch_mapping_field'
        );
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_batch_mapping_item',
            'akeneo_batch_mapping_item'
        );
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_batch_step_execution',
            'akeneo_batch_step_execution'
        );
    }
}
