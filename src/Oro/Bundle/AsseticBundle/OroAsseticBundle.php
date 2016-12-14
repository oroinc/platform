<?php

namespace Oro\Bundle\AsseticBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\AsseticBundle\DependencyInjection\Compiler\AsseticFilterPass;

class OroAsseticBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AsseticFilterPass());
    }
}
