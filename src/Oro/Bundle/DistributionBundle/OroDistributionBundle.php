<?php

namespace Oro\Bundle\DistributionBundle;

use Oro\Bundle\DistributionBundle\DependencyInjection\Compiler\HiddenRoutesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroDistributionBundle extends Bundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new HiddenRoutesPass());
    }
}
