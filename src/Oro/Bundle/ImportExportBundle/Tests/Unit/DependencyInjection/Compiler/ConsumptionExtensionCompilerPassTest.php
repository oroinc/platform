<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ConsumptionExtensionCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConsumptionExtensionCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConsumptionExtensionCompilerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ConsumptionExtensionCompilerPass();
    }

    public function testProcessWithoutDefinition(): void
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $extensionDef = $container->register('oro_ui.consumption_extension.request_context');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addTopicName', [Topics::PRE_IMPORT]],
                ['addTopicName', [Topics::IMPORT]],
                ['addTopicName', [Topics::PRE_EXPORT]],
                ['addTopicName', [Topics::EXPORT]],
                ['addTopicName', [Topics::POST_EXPORT]],
                ['addTopicName', [Topics::SEND_IMPORT_NOTIFICATION]],
                ['addTopicName', [Topics::SAVE_IMPORT_EXPORT_RESULT]]
            ],
            $extensionDef->getMethodCalls()
        );
    }
}
