<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\UIBundle\DependencyInjection\Compiler\AbstractWidgetProviderPass;

class AbstractWidgetProviderPassTest extends \PHPUnit_Framework_TestCase
{
    const SERVICE_ID = 'test_service';
    const TAG_NAME   = 'test_tag';

    public function testProcessNoProviderDefinition()
    {
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $compiler  = $this->getCompilerMock();

        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(self::SERVICE_ID)
            ->will($this->returnValue(false));
        $container->expects($this->never())
            ->method('findTaggedServiceIds');

        $compiler->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $compiler  = $this->getCompilerMock('chain_service', 'tag_name');

        $chainProvider = new Definition();
        $provider1     = new Definition();
        $provider2     = new Definition();
        $provider3     = new Definition();
        $provider4     = new Definition();

        $provider1->addTag(self::TAG_NAME, ['priority' => 100]);
        $provider2->addTag(self::TAG_NAME, ['priority' => -100]);
        $provider3->addTag(self::TAG_NAME);
        $provider4->addTag(self::TAG_NAME, ['priority' => 100]);

        $container->addDefinitions(
            [
                self::SERVICE_ID => $chainProvider,
                'provider1'      => $provider1,
                'provider2'      => $provider2,
                'provider3'      => $provider3,
                'provider4'      => $provider4,
            ]
        );

        $compiler->process($container);

        $this->assertEquals(
            [
                ['addProvider', [new Reference('provider2')]],
                ['addProvider', [new Reference('provider3')]],
                ['addProvider', [new Reference('provider1')]],
                ['addProvider', [new Reference('provider4')]],
            ],
            $chainProvider->getMethodCalls()
        );
    }

    /**
     * @return AbstractWidgetProviderPass
     */
    protected function getCompilerMock()
    {
        $compiler = $this->getMockForAbstractClass(
            'Oro\Bundle\UIBundle\DependencyInjection\Compiler\AbstractWidgetProviderPass',
            [],
            '',
            true,
            true,
            true,
            ['getChainProviderServiceId', 'getProviderTagName']
        );
        $compiler->expects($this->any())
            ->method('getChainProviderServiceId')
            ->will($this->returnValue(self::SERVICE_ID));
        $compiler->expects($this->any())
            ->method('getProviderTagName')
            ->will($this->returnValue(self::TAG_NAME));

        return $compiler;
    }
}
