<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SoapBundle\DependencyInjection\OroSoapExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroSoapExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroSoapExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
