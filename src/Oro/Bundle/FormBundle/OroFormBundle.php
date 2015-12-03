<?php

namespace Oro\Bundle\FormBundle;

use Oro\Bundle\FormBundle\DependencyInjection\Compiler\AutocompleteCompilerPass;
use Oro\Bundle\FormBundle\DependencyInjection\Compiler\FormCompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroFormBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AutocompleteCompilerPass());
        $container->addCompilerPass(new FormCompilerPass());
    }
}
