<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Monolog\Logger;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildMonologHandlersPass;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Bundle\MonologBundle\DependencyInjection\MonologExtension;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class BuildMonologHandlersPassTest extends \PHPUnit\Framework\TestCase
{
    private BuildMonologHandlersPass $compiler;

    protected function setUp(): void
    {
        $this->compiler = new BuildMonologHandlersPass();
    }

    /**
     * @dataProvider processVerbosityFilterProvider
     */
    public function testProcessVerbosityFilter(array $handler, array $verbosityLevels)
    {
        $container = new ExtendedContainerBuilder();
        $container->registerExtension(new MonologExtension());
        $container->setExtensionConfig('monolog', [['handlers' => [$handler]]]);

        $filterHandlerDef = $container->register($handler['id']);

        $this->compiler->process($container);

        $this->assertEquals(
            new Reference('monolog.handler.' . $handler['handler']),
            $filterHandlerDef->getArgument(1)
        );
        $this->assertEquals(
            $verbosityLevels,
            $filterHandlerDef->getArgument(2)
        );
    }

    public function testProcessWithEmptyConfigs()
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new MonologExtension());

        $this->compiler->process($container);
    }

    public function testProcessWithoutHandlers()
    {
        $container = new ExtendedContainerBuilder();
        $container->registerExtension(new MonologExtension());
        $container->setExtensionConfig('monolog', [['handlers' => []]]);

        $this->compiler->process($container);
    }

    public function testProcessWithInvalidHandler()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The attribute "name" must be set for path "monolog.handlers".');

        $container = new ExtendedContainerBuilder();
        $container->registerExtension(new MonologExtension());
        $container->setExtensionConfig('monolog', [['handlers' => [['type' => 'buffer']]]]);

        $this->compiler->process($container);
    }

    public function processVerbosityFilterProvider(): array
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
