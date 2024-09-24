<?php

namespace Oro\Bundle\NotificationBundle;

use Oro\Bundle\NotificationBundle\DependencyInjection\Compiler\EventsCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroNotificationBundle extends Bundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new EventsCompilerPass());
    }
}
