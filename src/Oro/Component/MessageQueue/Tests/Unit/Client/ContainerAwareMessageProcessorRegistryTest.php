<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Client\ContainerAwareMessageProcessorRegistry;
use Symfony\Component\DependencyInjection\Container;

class ContainerAwareMessageProcessorRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithoutAnyArgument()
    {
        new ContainerAwareMessageProcessorRegistry();
    }

    public function testShouldThrowExceptionIfProcessorIsNotSet()
    {
        $this->setExpectedException(
            \LogicException::class,
            'MessageProcessor was not found. processorName: "processor-name"'
        );

        $registry = new ContainerAwareMessageProcessorRegistry();
        $registry->get('processor-name');
    }

    public function testShouldThrowExceptionIfContainerIsNotSet()
    {
        $this->setExpectedException(\LogicException::class, 'Container was not set');

        $registry = new ContainerAwareMessageProcessorRegistry();
        $registry->set('processor-name', 'processor-id');

        $registry->get('processor-name');
    }

    public function testShouldThrowExceptionIfInstanceOfMessageProcessorIsInvalid()
    {
        $this->setExpectedException(\LogicException::class, 'Container was not set');

        $processor = new \stdClass();

        $container = new Container();
        $container->set('processor-id', $processor);

        $registry = new ContainerAwareMessageProcessorRegistry();
        $registry->set('processor-name', 'processor-id');

        $registry->get('processor-name');
    }

    public function testShouldReturnInstanceOfMessageProcessor()
    {
        $this->setExpectedException(\LogicException::class, 'Container was not set');

        $processor = $this->createMessageProcessorMock();

        $container = new Container();
        $container->set('processor-id', $processor);

        $registry = new ContainerAwareMessageProcessorRegistry();
        $registry->set('processor-name', 'processor-id');

        $this->assertSame($processor, $registry->get('processor-name'));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProcessorInterface
     */
    protected function createMessageProcessorMock()
    {
        return $this->getMock(MessageProcessorInterface::class);
    }
}
