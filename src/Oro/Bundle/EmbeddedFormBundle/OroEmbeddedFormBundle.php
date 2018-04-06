<?php

namespace Oro\Bundle\EmbeddedFormBundle;

use Oro\Bundle\EmbeddedFormBundle\DependencyInjection\Compiler\EmbeddedFormPass;
use Oro\Bundle\EmbeddedFormBundle\DependencyInjection\Compiler\LayoutManagerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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
