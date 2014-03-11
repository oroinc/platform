<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigration;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigDumper;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;
use Symfony\Component\HttpKernel\KernelInterface;

class PostUpMigrationListener
{
    /**
     * @var ConfigDumper
     */
    protected $configDumper;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @param ConfigDumper $configDumper
     */
    public function __construct(ConfigDumper $configDumper, KernelInterface $kernel)
    {
        $this->configDumper = $configDumper;
        $this->kernel = $kernel;
    }

    /**
     * POST UP event handler
     *
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(
            new UpdateEntityConfigMigration($this->configDumper, $this->kernel)
        );
    }
}
