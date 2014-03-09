<?php

namespace Oro\Bundle\BatchBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;

class OroBatchBundle extends Migration implements RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * @inheritdoc
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * @inheritdoc
     */
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
