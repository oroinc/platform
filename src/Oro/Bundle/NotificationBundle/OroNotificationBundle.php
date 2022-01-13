<?php

namespace Oro\Bundle\NotificationBundle;

use Oro\Bundle\NotificationBundle\DependencyInjection\Compiler\EventsCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The NotificationBundle bundle class.
 */
class OroNotificationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new EventsCompilerPass());
    }
}
