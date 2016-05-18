<?php
namespace Oro\Bundle\MessagingBundle;

use Oro\Bundle\MessagingBundle\DependencyInjection\Compiler\BuildExtensionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroMessagingBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new BuildExtensionsPass());
    }
}
