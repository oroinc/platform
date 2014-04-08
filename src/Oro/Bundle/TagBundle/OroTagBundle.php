<?php

namespace Oro\Bundle\TagBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\TagBundle\DependencyInjection\Compiler\TagManagerPass;

class OroTagBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TagManagerPass());
    }
}
