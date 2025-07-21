<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\WorkflowBundle\DependencyInjection\OroWorkflowExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroWorkflowExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroWorkflowExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
