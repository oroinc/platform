<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Monolog\Logger;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildMonologHandlersPass;
use Symfony\Bundle\MonologBundle\DependencyInjection\Configuration;
use Symfony\Bundle\MonologBundle\DependencyInjection\MonologExtension;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BuildMonologHandlersPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var BuildMonologHandlersPass */
    private $buildMonologHandlersPass;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->buildMonologHandlersPass = new BuildMonologHandlersPass();
    }

    /**
     * @dataProvider processConsoleErrorProvider
     *
     * @param array $handler
     * @param int $level
     */
    public function testProcessConsoleError(array $handler, $level)
    {
        $configuration = new Configuration();
        $monologExtension = $this->createMock(MonologExtension::class);
        $monologExtension->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $container */
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())
            ->method('getExtension')
            ->with('monolog')
            ->willReturn($monologExtension);
        $container->expects($this->once())
            ->method('getExtensionConfig')
            ->with('monolog')
            ->willReturn([
                ['handlers' => [$handler]]
            ]);

        $consoleErrorHandler = $this->createMock(Definition::class);
        $container->expects($this->once())
            ->method('getDefinition')
            ->with($handler['id'])
            ->willReturn($consoleErrorHandler);

        $consoleErrorHandler->expects($this->exactly(2))
            ->method('setArgument')
            ->withConsecutive(
                [1, new Reference('monolog.handler.' . $handler['handler'])],
                [2, $level]
            );

        $this->buildMonologHandlersPass->process($container);
    }

    /**
     * @dataProvider processVerbosityFilterProvider
     */
    public function testProcessVerbosityFilter(array $handler, array $verbosityLevels)
    {
        $configuration = new Configuration();
        $monologExtension = $this->createMock(MonologExtension::class);
        $monologExtension->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $container */
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())
            ->method('getExtension')
            ->with('monolog')
            ->willReturn($monologExtension);
        $container->expects($this->once())
            ->method('getExtensionConfig')
            ->with('monolog')
            ->willReturn([
                ['handlers' => [$handler]]
            ]);

        $consoleErrorHandler = $this->createMock(Definition::class);
        $container->expects($this->once())
            ->method('getDefinition')
            ->with($handler['id'])
            ->willReturn($consoleErrorHandler);

        $consoleErrorHandler->expects($this->exactly(2))
            ->method('setArgument')
            ->withConsecutive(
                [1, new Reference('monolog.handler.' . $handler['handler'])],
                [2, $verbosityLevels]
            );

        $this->buildMonologHandlersPass->process($container);
    }

    public function testProcessWithEmptyConfigs()
    {
        $configuration = new Configuration();
        $monologExtension = $this->createMock(MonologExtension::class);
        $monologExtension->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $container */
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())
            ->method('getExtension')
            ->with('monolog')
            ->willReturn($monologExtension);
        $container->expects($this->once())
            ->method('getExtensionConfig')
            ->with('monolog')
            ->willReturn([]);

        $container->expects($this->never())
            ->method('getDefinition');

        $this->buildMonologHandlersPass->process($container);
    }

    public function testProcessWithoutHandlers()
    {
        $configuration = new Configuration();
        $monologExtension = $this->createMock(MonologExtension::class);
        $monologExtension->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $container */
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())
            ->method('getExtension')
            ->with('monolog')
            ->willReturn($monologExtension);
        $container->expects($this->once())
            ->method('getExtensionConfig')
            ->with('monolog')
            ->willReturn([
                ['handlers' => []]
            ]);

        $container->expects($this->never())
            ->method('getDefinition');

        $this->buildMonologHandlersPass->process($container);
    }

    public function testProcessWithInvalidHandler()
    {
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $container */
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())
            ->method('getExtension')
            ->with('monolog')
            ->willReturn([
                ['handlers' => [
                    ['type' => 'buffer'],
                    ['type' => 'service', 'id' => 'other.handler']
                ]]
            ]);

        $container->expects($this->never())
            ->method('getDefinition');

        $this->buildMonologHandlersPass->process($container);
    }

    /**
     * @return array
     */
    public function processConsoleErrorProvider()
    {
        return [
            'simple console error handler' => [
                'handler' => [
                    'name' => 'console_error',
                    'type' => 'service',
                    'id' => 'oro_message_queue.log.handler.console_error',
                    'handler' => 'nested',
                ],
                'level' => 'DEBUG',
            ],
            'with level' => [
                'handler' => [
                    'name' => 'console_error',
                    'type' => 'service',
                    'id' => 'oro_message_queue.log.handler.console_error',
                    'handler' => 'nested',
                    'level' => 'NOTICE'
                ],
                'level' => 'NOTICE',
            ]
        ];
    }

    /**
     * @return array
     */
    public function processVerbosityFilterProvider()
    {
        return [
            'simple verbosity filter handler' => [
                'handler' => [
                    'name' => 'verbosity_filter',
                    'type' => 'service',
                    'id' => 'oro_message_queue.log.handler.verbosity_filter',
                    'handler' => 'nested',
                ],
                'verbosity_levels' => [],
            ],
            'with verbosity_levels' => [
                'handler' => [
                    'name' => 'verbosity_filter',
                    'type' => 'service',
                    'id' => 'oro_message_queue.log.handler.verbosity_filter',
                    'handler' => 'nested',
                    'verbosity_levels' => ['VERBOSITY_QUIET' => 'DEBUG']
                ],
                'verbosity_levels' => [
                    OutputInterface::VERBOSITY_QUIET => Logger::DEBUG,
                    OutputInterface::VERBOSITY_NORMAL => Logger::WARNING,
                    OutputInterface::VERBOSITY_VERBOSE => Logger::NOTICE,
                    OutputInterface::VERBOSITY_VERY_VERBOSE => Logger::INFO,
                    OutputInterface::VERBOSITY_DEBUG => Logger::DEBUG,
                ],
            ]
        ];
    }
}
