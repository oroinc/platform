<?php

namespace Oro\Bundle\CurrencyBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\CurrencyBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;

class OroCurrencyBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
        parent::build($container);
    }
}
