<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Processor;

use Oro\Bundle\EmailBundle\Processor\VariableProcessorInterface;
use Oro\Bundle\EmailBundle\Processor\VariableProcessorRegistry;
use Oro\Component\DependencyInjection\Exception\UnknownAliasException;
use Oro\Component\DependencyInjection\ServiceLinkRegistry;

class VariableProcessorRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ServiceLinkRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $serviceLinkRegistry;

    /** @var  VariableProcessorRegistry */
    private $registry;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->serviceLinkRegistry = $this->createMock(ServiceLinkRegistry::class);
        $this->registry = new VariableProcessorRegistry();
        $this->registry->setServiceLinkRegistry($this->serviceLinkRegistry);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->serviceLinkRegistry, $this->registry);
    }

    public function testRegisterAndGet()
    {
        $processor = $this->getProcessorMock();
        $this->serviceLinkRegistry->expects($this->once())->method('get')->with('test')->willReturn($processor);
        $this->assertSame($this->registry->get('test'), $processor);
    }

    public function testHas()
    {
        $this->serviceLinkRegistry->expects($this->at(0))->method('has')->with('exists')->willReturn(true);
        $this->serviceLinkRegistry->expects($this->at(1))->method('has')->with('not_exists')->willReturn(false);

        $this->assertTrue($this->registry->has('exists'));
        $this->assertFalse($this->registry->has('not_exists'));
    }

    /**
     * @expectedException \Oro\Bundle\EmailBundle\Exception\UnknownVariableProcessorException
     * @expectedExceptionMessage Unknown variable processor with alias `test`
     */
    public function testGetUnregisteredException()
    {
        $this->serviceLinkRegistry->expects($this->once())
            ->method('get')->with('test')
            ->willThrowException(new UnknownAliasException('test'));

        $this->registry->get('test');
    }

    public function testExceptionOnInvalidServiceInterface()
    {
        $handler = (object)[];
        $this->serviceLinkRegistry->expects($this->once())->method('get')->with('test')->willReturn($handler);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Variable processor `%s` with `%s` alias must implement %s.',
                get_class($handler),
                'test',
                VariableProcessorInterface::class
            )
        );

        $this->registry->get('test');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|VariableProcessorInterface
     */
    protected function getProcessorMock()
    {
        $handler = $this->getMockBuilder(VariableProcessorInterface::class)->disableOriginalConstructor()->getMock();

        return $handler;
    }
}
