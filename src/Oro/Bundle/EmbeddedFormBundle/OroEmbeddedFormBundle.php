<?php

namespace Oro\Bundle\EmbeddedFormBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\EmbeddedFormBundle\DependencyInjection\Compiler\EmbeddedFormPass;
use Oro\Bundle\EmbeddedFormBundle\DependencyInjection\Compiler\LayoutManagerPass;

class OroEmbeddedFormBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new EmbeddedFormPass());
        $container->addCompilerPass(new LayoutManagerPass());
    }
}
