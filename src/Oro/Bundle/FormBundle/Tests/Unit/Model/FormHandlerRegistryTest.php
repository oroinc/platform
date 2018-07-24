<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Model\FormHandlerRegistry;
use Oro\Component\DependencyInjection\Exception\UnknownAliasException;
use Oro\Component\DependencyInjection\ServiceLinkRegistry;

class FormHandlerRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ServiceLinkRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $serviceLinkRegistry;

    /** @var FormHandlerRegistry */
    private $registry;

    protected function setUp()
    {
        $this->serviceLinkRegistry = $this->createMock(ServiceLinkRegistry::class);
        $this->registry = new FormHandlerRegistry();
        $this->registry->setServiceLinkRegistry($this->serviceLinkRegistry);
    }

    public function testRegisterAndGet()
    {
        $handler = $this->getHandlerMock();
        $this->serviceLinkRegistry->expects($this->once())->method('get')->with('test')->willReturn($handler);
        $this->assertSame($this->registry->get('test'), $handler);
    }

    /**
     */
    public function testHas()
    {
        $this->serviceLinkRegistry->expects($this->at(0))->method('has')->with('exists')->willReturn(true);
        $this->serviceLinkRegistry->expects($this->at(1))->method('has')->with('not_exists')->willReturn(false);

        $this->assertTrue($this->registry->has('exists'));
        $this->assertFalse($this->registry->has('not_exists'));
    }

    /**
     * @expectedException \Oro\Bundle\FormBundle\Exception\UnknownFormHandlerException
     * @expectedExceptionMessage Unknown form handler with alias `test`
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
                'Form data provider `%s` with `%s` alias must implement %s.',
                get_class($handler),
                'test',
                FormHandlerInterface::class
            )
        );

        $this->registry->get('test');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|FormHandlerInterface
     */
    protected function getHandlerMock()
    {
        $handler = $this->getMockBuilder(FormHandlerInterface::class)->disableOriginalConstructor()->getMock();

        return $handler;
    }
}
