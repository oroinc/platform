<?php

namespace Oro\Bundle\TestFrameworkBundle;

use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\TagsInformationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroTestFrameworkBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TagsInformationPass());
    }
}
