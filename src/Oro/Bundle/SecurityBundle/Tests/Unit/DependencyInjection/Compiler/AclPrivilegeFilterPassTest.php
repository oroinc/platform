<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\AclPrivilegeFilterPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AclPrivilegeFilterPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();

        $container->register('filter1')
            ->addTag('oro.security.filter.acl_privilege', []);
        $container->register('filter2')
            ->addTag('oro.security.filter.acl_privilege', ['priority' => -10]);
        $container->register('filter3')
            ->addTag('oro.security.filter.acl_privilege', ['priority' => 10]);

        $chainFilter = $container->register('oro_security.filter.configurable_permission_filter');

        $compiler = new AclPrivilegeFilterPass();
        $compiler->process($container);

        self::assertEquals(
            [
                ['addConfigurableFilter', [new Reference('filter2'), 'filter2']],
                ['addConfigurableFilter', [new Reference('filter1'), 'filter1']],
                ['addConfigurableFilter', [new Reference('filter3'), 'filter3']]
            ],
            $chainFilter->getMethodCalls()
        );
    }
}
