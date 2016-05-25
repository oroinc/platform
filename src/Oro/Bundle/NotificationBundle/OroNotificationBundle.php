<?php

namespace Oro\Bundle\NotificationBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\NotificationBundle\DependencyInjection\Compiler\EventsCompilerPass;
use Oro\Bundle\NotificationBundle\DependencyInjection\Compiler\NotificationHandlerPass;
use Oro\Bundle\NotificationBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;

class OroNotificationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        
        $container->addCompilerPass(new NotificationHandlerPass());
        $container->addCompilerPass(new EventsCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
    }
}   
