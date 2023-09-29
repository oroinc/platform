<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\OrganizationBundle\DependencyInjection\OroOrganizationExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroOrganizationExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroOrganizationExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
