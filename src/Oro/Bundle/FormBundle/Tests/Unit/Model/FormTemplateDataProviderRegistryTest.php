<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Oro\Bundle\FormBundle\Model\FormTemplateDataProviderRegistry;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Oro\Component\DependencyInjection\Exception\UnknownAliasException;
use Oro\Component\DependencyInjection\ServiceLinkRegistry;

class FormTemplateDataProviderRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormTemplateDataProviderRegistry */
    private $registry;

    /** @var ServiceLinkRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $serviceLinkRegistry;

    protected function setUp()
    {
        $this->serviceLinkRegistry = $this->createMock(ServiceLinkRegistry::class);
        $this->registry = new FormTemplateDataProviderRegistry();
        $this->registry->setServiceLinkRegistry($this->serviceLinkRegistry);
    }

    public function testGet()
    {
        /** @var FormTemplateDataProviderInterface|\PHPUnit\Framework\MockObject\MockObject $provider */
        $provider = $this->createMock(FormTemplateDataProviderInterface::class);

        $this->serviceLinkRegistry->expects($this->once())->method('get')->with('test')->willReturn($provider);

        $this->assertSame($this->registry->get('test'), $provider);
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
                FormTemplateDataProviderInterface::class
            )
        );

        $this->registry->get('test');
    }

    /**
     * @expectedException \Oro\Bundle\FormBundle\Exception\UnknownProviderException
     * @expectedExceptionMessage Unknown provider with alias `test`.
     */
    public function testGetUnregisteredException()
    {
        $this->serviceLinkRegistry->expects($this->once())
            ->method('get')->with('test')
            ->willThrowException(new UnknownAliasException('test'));

        $this->registry->get('test');
    }

    public function testHas()
    {
        $this->serviceLinkRegistry->expects($this->at(0))->method('has')->with('alias')->willReturn(true);
        $this->serviceLinkRegistry->expects($this->at(1))->method('has')->with('nonexistent')->willReturn(false);
        $this->assertTrue($this->registry->has('alias'));
        $this->assertFalse($this->registry->has('nonexistent'));
    }
}
