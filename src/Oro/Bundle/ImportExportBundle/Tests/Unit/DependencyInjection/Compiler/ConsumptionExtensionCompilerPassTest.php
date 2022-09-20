<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ImportExportBundle\Async\Topic\ExportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\ImportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PostExportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreExportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreImportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\SaveImportExportResultTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\SendImportNotificationTopic;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ConsumptionExtensionCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConsumptionExtensionCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    private ConsumptionExtensionCompilerPass $compiler;

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

        $tagName = 'oro_message_queue.consumption.extension.topic';

        $container->register(PreImportTopic::class)->addTag($tagName);
        $container->register(ImportTopic::class)->addTag($tagName);
        $container->register(PreExportTopic::class)->addTag($tagName);
        $container->register(ExportTopic::class)->addTag($tagName);
        $container->register(PostExportTopic::class)->addTag($tagName);
        $container->register(SendImportNotificationTopic::class)->addTag($tagName);
        $container->register(SaveImportExportResultTopic::class)->addTag($tagName);

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addTopicName', [PreImportTopic::getName()]],
                ['addTopicName', [ImportTopic::getName()]],
                ['addTopicName', [PreExportTopic::getName()]],
                ['addTopicName', [ExportTopic::getName()]],
                ['addTopicName', [PostExportTopic::getName()]],
                ['addTopicName', [SendImportNotificationTopic::getName()]],
                ['addTopicName', [SaveImportExportResultTopic::getName()]],
            ],
            $extensionDef->getMethodCalls()
        );
    }
}
