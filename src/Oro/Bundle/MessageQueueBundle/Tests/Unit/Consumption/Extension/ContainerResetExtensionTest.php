<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ClearerInterface;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ContainerResetExtension;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\Mocks\ChainExtensionAwareClearer;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class ContainerResetExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ClearerInterface */
    private $clearer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ChainExtensionAwareClearer */
    private $chainExtensionAwareClearer;

    /** @var ContainerResetExtension */
    private $extension;

    protected function setUp()
    {
        $this->clearer = $this->createMock(ClearerInterface::class);
        $this->chainExtensionAwareClearer = $this->createMock(ChainExtensionAwareClearer::class);

        $this->extension = new ContainerResetExtension(
            [$this->clearer, $this->chainExtensionAwareClearer]
        );
    }

    public function testSetPersistentProcessors()
    {
        self::assertAttributeSame([], 'persistentProcessors', $this->extension);

        $this->extension->setPersistentProcessors(['processor1']);
        self::assertAttributeEquals(['processor1' => true], 'persistentProcessors', $this->extension);

        $this->extension->setPersistentProcessors(['processor2']);
        self::assertAttributeEquals(
            ['processor1' => true, 'processor2' => true],
            'persistentProcessors',
            $this->extension
        );
    }

    public function testSetChainExtension()
    {
        $chainExtension = $this->createMock(ExtensionInterface::class);

        $this->chainExtensionAwareClearer->expects(self::once())
            ->method('setChainExtension')
            ->with(self::identicalTo($chainExtension));

        $this->extension->setChainExtension($chainExtension);
    }

    public function testOnPostReceivedShouldCallClearersForPersistentProcessor()
    {
        $message = new NullMessage();
        $message->setProperties([Config::PARAMETER_PROCESSOR_NAME => 'test_processor']);

        $logger = $this->createMock(LoggerInterface::class);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessage($message);
        $context->setLogger($logger);

        $this->clearer->expects(self::once())
            ->method('clear')
            ->with(self::isInstanceOf($logger));
        $this->chainExtensionAwareClearer->expects(self::once())
            ->method('clear')
            ->with(self::isInstanceOf($logger));

        $this->extension->onPostReceived($context);
    }

    public function testOnPostReceivedShouldNotCallClearersForPersistentProcessor()
    {
        $message = new NullMessage();
        $message->setProperties([Config::PARAMETER_PROCESSOR_NAME => 'test_processor']);

        $logger = $this->createMock(LoggerInterface::class);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessage($message);
        $context->setLogger($logger);

        $this->clearer->expects(self::never())
            ->method('clear');
        $this->chainExtensionAwareClearer->expects(self::never())
            ->method('clear');

        $this->extension->setPersistentProcessors(['test_processor']);
        $this->extension->onPostReceived($context);
    }
}
