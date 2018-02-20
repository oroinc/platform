<?php

namespace Oro\Bundle\QueryDesignerBundle;

use Oro\Bundle\QueryDesignerBundle\DependencyInjection\Compiler\ConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroQueryDesignerBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ConfigurationPass());
    }
}
