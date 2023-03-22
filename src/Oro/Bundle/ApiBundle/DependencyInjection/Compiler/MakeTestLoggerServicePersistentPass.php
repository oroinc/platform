<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\RegisterPersistentServicesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds the `oro_api.tests.customize_form_data_logger` service to persistent services in MQ config
 */
class MakeTestLoggerServicePersistentPass extends RegisterPersistentServicesPass
{
    protected function getPersistentServices(ContainerBuilder $container)
    {
        return ['oro_api.tests.customize_form_data_logger'];
    }
}
