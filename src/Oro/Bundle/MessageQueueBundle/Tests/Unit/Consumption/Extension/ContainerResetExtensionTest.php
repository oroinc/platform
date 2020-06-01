<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ClearerInterface;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ContainerResetExtension;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\Mocks\ChainExtensionAwareClearer;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ContainerResetExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var MockObject|ClearerInterface */
    private $clearer1;

    /** @var MockObject|ClearerInterface */
    private $clearer2;

    /** @var ChainExtensionAwareClearer|MockObject */
    private $chainExtensionAwareClearer;

    /** @var ContainerResetExtension */
    private $extension;

    /** @var MockObject|LoggerInterface */
    private $logger;

    protected function setUp(): void
    {
        $this->clearer1 = $this->getMockBuilder(ClearerInterface::class)
            ->addMethods(['setChainExtension'])
            ->getMockForAbstractClass();
        $this->clearer2 = $this->getMockBuilder(ClearerInterface::class)
            ->addMethods(['setChainExtension'])
            ->getMockForAbstractClass();
        $this->chainExtensionAwareClearer = $this->createMock(ChainExtensionAwareClearer::class);

        $this->extension = new ContainerResetExtension([
            $this->clearer1,
            $this->clearer2,
            $this->chainExtensionAwareClearer
        ]);

        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testOnPostReceivedForPersistentAndNonPersistentProcessors()
    {
        $this->extension->setPersistentProcessors(['persistent_processor1']);
        $this->extension->setPersistentProcessors(['persistent_processor2']);

        // verify that clearers are called only for non-persistent processors
        $this->clearer1->expects(static::exactly(1))->method('clear')->with(static::identicalTo($this->logger));
        $this->clearer2->expects(static::exactly(1))->method('clear')->with(static::identicalTo($this->logger));

        $this->extension->onPostReceived($this->createMessageContextForProcessor('non_persistent_processor'));

        $this->clearer1->expects(static::never())->method('clear');
        $this->clearer2->expects(static::never())->method('clear');

        // processing in different order to verify that subsequent calls to setPersistentProcessors
        // did not wipe out previously set persistent processors
        $this->extension->onPostReceived($this->createMessageContextForProcessor('persistent_processor2'));
        $this->extension->onPostReceived($this->createMessageContextForProcessor('persistent_processor1'));
    }

    public function testSetChainExtensionSetsItOnlyOnChainExtensionAwareInterfaceClearers()
    {
        $chainExtension = $this->createMock(ExtensionInterface::class);

        $this->clearer1->expects(static::never())->method('setChainExtension');
        $this->clearer2->expects(static::never())->method('setChainExtension');
        $this->chainExtensionAwareClearer->expects(static::once())
            ->method('setChainExtension')
            ->with(static::identicalTo($chainExtension));

        $this->extension->setChainExtension($chainExtension);
    }

    private function createMessageContextForProcessor(string $processorName): Context
    {
        $message = new Message();
        $message->setProperties([Config::PARAMETER_PROCESSOR_NAME => $processorName]);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessage($message);

        $context->setLogger($this->logger);

        return $context;
    }
}
