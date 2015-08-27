<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\ConstantsPass;

class ConstantsPassTest extends \PHPUnit_Framework_TestCase
{
    protected $expectedParameters = [
        'oro_ui.widget_provider.view_actions.page_type' => ActivityScope::VIEW_PAGE,
        'oro_ui.widget_provider.update_actions.page_type' => ActivityScope::UPDATE_PAGE,
    ];

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $compiler = new ConstantsPass();
        $compiler->process($container);

        $this->assertEquals($this->expectedParameters, $container->getParameterBag()->all());
    }
}
