<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\DependencyInjection;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TestApiPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $this->disableApiProcessor($container);
    }

    private function disableApiProcessor(ContainerBuilder $container): void
    {
        $processorsToBeDisabled = [
            'oro_api.collect_resources.load_dictionaries',
            'oro_api.collect_resources.load_custom_entities',
        ];
        foreach ($processorsToBeDisabled as $processorServiceId) {
            DependencyInjectionUtil::disableApiProcessor(
                $container,
                $processorServiceId,
                'test_empty_based_on_rest_json_api'
            );
        }
    }
}
