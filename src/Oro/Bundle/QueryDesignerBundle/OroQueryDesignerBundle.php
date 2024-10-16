<?php

namespace Oro\Bundle\QueryDesignerBundle;

use Oro\Bundle\QueryDesignerBundle\DependencyInjection\Compiler\RegisterCollapsedAssociationsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroQueryDesignerBundle extends Bundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterCollapsedAssociationsPass());
    }
}
