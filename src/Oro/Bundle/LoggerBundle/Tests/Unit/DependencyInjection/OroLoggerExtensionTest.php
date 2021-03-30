<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\LoggerBundle\DependencyInjection\OroLoggerExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroLoggerExtensionTest extends ExtensionTestCase
{
    /** @var OroLoggerExtension */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->extension = new OroLoggerExtension();
    }

    public function testLoad()
    {
        $this->loadExtension($this->extension);

        $expectedServices = [
            'oro_logger.event_subscriber.console_command',
            'oro_logger.log_level_config_provider'
        ];

        $this->assertDefinitionsLoaded($expectedServices);
    }
}
