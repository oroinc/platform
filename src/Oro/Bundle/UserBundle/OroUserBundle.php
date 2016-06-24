<?php

namespace Oro\Bundle\UserBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\UserBundle\DependencyInjection\Compiler\PermissionCategoryPass;
use Oro\Bundle\UserBundle\DependencyInjection\Compiler\EscapeWsseConfigurationPass;

class OroUserBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new EscapeWsseConfigurationPass());
        $container->addCompilerPass(new PermissionCategoryPass());
    }
}
