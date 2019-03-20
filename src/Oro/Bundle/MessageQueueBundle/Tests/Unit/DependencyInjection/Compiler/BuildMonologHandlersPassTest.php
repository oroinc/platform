<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildMonologHandlersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class BuildMonologHandlersPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var BuildMonologHandlersPass */
    private $buildMonologHandlersPass;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->buildMonologHandlersPass = new BuildMonologHandlersPass();
    }

    /**
     * @dataProvider processProvider
     *
     * @param array $handler
     * @param int $bufferSize
     * @param int $level
     * @param bool $bubble
     * @param bool $flushOnOverflow
     */
    public function testProcess(array $handler, $bufferSize, $level, $bubble, $flushOnOverflow)
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())
            ->method('getExtensionConfig')
            ->with('monolog')
            ->willReturn([
                ['handlers' => [$handler]]
            ]);

        $nestedHandlerDefinition = $this->createMock(Definition::class);
        $container->expects($this->at(1))
            ->method('getDefinition')
            ->with('monolog.handler.nested')
            ->willReturn($nestedHandlerDefinition);

        $consoleErrorHandler = $this->createMock(Definition::class);
        $consoleErrorHandler->expects($this->once())
            ->method('setArguments')
            ->with([$nestedHandlerDefinition, $bufferSize, $level, $bubble, $flushOnOverflow]);
        $container->expects($this->at(2))
            ->method('getDefinition')
            ->with('oro_message_queue.log.handler.console_error')
            ->willReturn($consoleErrorHandler);

        $this->buildMonologHandlersPass->process($container);
    }

    public function testProcessWithEmptyConfigs()
    {
        $container = $this->createMock(ContainerBuilder::class);
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
        $container = $this->createMock(ContainerBuilder::class);
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
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())
            ->method('getExtensionConfig')
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
    public function processProvider()
    {
        return [
            'simple console error handler' => [
                'handler' => [
                    'type' => 'service',
                    'id' => 'oro_message_queue.log.handler.console_error',
                    'handler' => 'nested',
                ],
                'bufferSize' => 0,
                'level' => 100,
                'bubble' => true,
                'flushOnOverflow' => false
            ],
            'with buffer size' => [
                'handler' => [
                    'type' => 'service',
                    'id' => 'oro_message_queue.log.handler.console_error',
                    'handler' => 'nested',
                    'buffer_size' => 500
                ],
                'bufferSize' => 500,
                'level' => 100,
                'bubble' => true,
                'flushOnOverflow' => false
            ],
            'with level' => [
                'handler' => [
                    'type' => 'service',
                    'id' => 'oro_message_queue.log.handler.console_error',
                    'handler' => 'nested',
                    'level' => 200
                ],
                'bufferSize' => 0,
                'level' => 200,
                'bubble' => true,
                'flushOnOverflow' => false
            ],
            'with bubble' => [
                'handler' => [
                    'type' => 'service',
                    'id' => 'oro_message_queue.log.handler.console_error',
                    'handler' => 'nested',
                    'bubble' => false
                ],
                'bufferSize' => 0,
                'level' => 100,
                'bubble' => false,
                'flushOnOverflow' => false
            ],
            'with flush on overflow' => [
                'handler' => [
                    'type' => 'service',
                    'id' => 'oro_message_queue.log.handler.console_error',
                    'handler' => 'nested',
                    'flush_on_overflow' => true
                ],
                'bufferSize' => 0,
                'level' => 100,
                'bubble' => true,
                'flushOnOverflow' => true
            ]
        ];
    }
}
