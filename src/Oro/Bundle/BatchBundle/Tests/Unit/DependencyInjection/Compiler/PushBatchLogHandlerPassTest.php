<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\BatchBundle\DependencyInjection\Compiler\PushBatchLogHandlerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class PushBatchLogHandlerPassTest extends \PHPUnit\Framework\TestCase
{
    private PushBatchLogHandlerPass $pushBatchLogHandlerPass;

    protected function setUp(): void
    {
        $this->pushBatchLogHandlerPass = new PushBatchLogHandlerPass();
    }

    public function testProcessWithBatchChannel(): void
    {
        $logger = new Definition();
        $container = $this->getContainerBuilderMock($logger);

        $container->expects(self::any())
            ->method('getDefinition')
            ->willReturnMap([['monolog.logger.batch', $logger]]);

        $this->pushBatchLogHandlerPass->process($container);

        $calls = $logger->getMethodCalls();
        self::assertEquals('pushHandler', $calls[0][0]);
        self::assertInstanceOf(Reference::class, $calls[0][1][0]);
        self::assertEquals('oro_batch.monolog.handler.batch_log_handler', (string)$calls[0][1][0]);
    }

    public function testProcessWithoutBatchChannel(): void
    {
        $container = $this->getContainerBuilderMock();

        $container->expects(self::never())
            ->method('getDefinition');

        $this->pushBatchLogHandlerPass->process($container);
    }

    private function getContainerBuilderMock(
        Definition $logger = null
    ): ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject {
        $container = $this->createMock(ContainerBuilder::class);

        $container->expects(self::any())
            ->method('has')
            ->willReturnMap([['monolog.logger.batch', null !== $logger]]);

        return $container;
    }
}
