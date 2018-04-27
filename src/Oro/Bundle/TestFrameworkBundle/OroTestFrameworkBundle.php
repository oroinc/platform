<?php

namespace Oro\Bundle\TestFrameworkBundle;

use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\CheckReferenceCompilerPass;
use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\ClientCompilerPass;
use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\TagsInformationPass;
use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\TestSessionListenerCompilerPass;
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
        $container->addCompilerPass(new CheckReferenceCompilerPass());
        $container->addCompilerPass(new ClientCompilerPass());
        $container->addCompilerPass(new TestSessionListenerCompilerPass());
    }
}
