<?php

namespace Oro\Bundle\DraftBundle;

use Oro\Bundle\DraftBundle\DependencyInjection\Compiler\RouterPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroDraftBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RouterPass());
    }
}
