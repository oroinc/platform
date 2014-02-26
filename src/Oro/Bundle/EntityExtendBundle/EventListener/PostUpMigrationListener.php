<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigration;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendSchemaGenerator;
use Oro\Bundle\InstallerBundle\Migrations\Event\PostMigrationEvent;

class PostUpMigrationListener
{
    /**
     * @var ExtendSchemaGenerator
     */
    protected $schemaGenerator;

    /**
     * @var ExtendConfigDumper
     */
    protected $configDumper;

    public function __construct(ExtendSchemaGenerator $schemaGenerator, ExtendConfigDumper $configDumper)
    {
        $this->schemaGenerator = $schemaGenerator;
        $this->configDumper    = $configDumper;
    }

    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(
            new UpdateExtendConfigMigration(
                $this->schemaGenerator,
                $this->configDumper
            )
        );
    }
}
