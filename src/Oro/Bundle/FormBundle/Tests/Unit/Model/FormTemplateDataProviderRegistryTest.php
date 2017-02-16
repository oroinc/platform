<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Oro\Bundle\FormBundle\Model\FormTemplateDataProviderRegistry;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FormTemplateDataProviderRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $containerMock;

    /** @var FormTemplateDataProviderRegistry */
    private $registry;

    protected function setUp()
    {
        $this->containerMock = $this->createMock(ContainerInterface::class);
        $this->registry = new FormTemplateDataProviderRegistry($this->containerMock);
    }

    public function testAddAndGet()
    {
        /** @var FormTemplateDataProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->createMock(FormTemplateDataProviderInterface::class);

        $this->containerMock->expects($this->once())
            ->method('get')->with('provider.service.name')->willReturn($provider);

        $this->registry->addProviderService('provider.service.name', 'test');

        $this->assertSame($this->registry->get('test'), $provider);
        $this->assertSame($this->registry->get('test'), $provider); //once more to test access container only once
    }

    public function testAddInvalidArgument()
    {
        $instance = new \stdClass();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Can\'t add provider service.' .
            ' The first argument MUST be service name or instance of' .
            ' `Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface`. `stdClass` given.'
        );

        $this->registry->addProviderService($instance, 'alias');
    }

    public function testRegisterOverridesPreviousByAlias()
    {
        $provider2 = $this->getMockBuilder(FormTemplateDataProviderInterface::class)->getMock();
        $this->containerMock->expects($this->once())
            ->method('get')->with('service.two')->willReturn($provider2);

        $this->registry->addProviderService('service.one', 'test');
        $this->registry->addProviderService('service.two', 'test');
        $this->assertSame($this->registry->get('test'), $provider2);
    }

    public function testBadServiceInterface()
    {
        $badProvider = new \stdClass();

        $this->containerMock->expects($this->once())
            ->method('get')->with('suspicious.provider')->willReturn($badProvider);

        $this->registry->addProviderService('suspicious.provider', 'test');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage(
            'Form data provider service `stdClass` with `test` alias' .
            ' must implement Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface.'
        );

        $this->registry->get('test');
    }

    /**
     * @expectedException \Oro\Bundle\FormBundle\Exception\UnknownProviderException
     * @expectedExceptionMessage Unknown provider with alias `test`
     */
    public function testGetUnregisteredException()
    {
        $this->registry->get('test');
    }

    public function testHas()
    {
        $this->registry->addProviderService('service.one', 'alias');
        $this->assertTrue($this->registry->has('alias'));
        $this->assertFalse($this->registry->has('nonexistent'));
    }
}
