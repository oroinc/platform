<?php

namespace Oro\Bundle\MaintenanceBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MaintenanceBundle\DependencyInjection\OroMaintenanceExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroMaintenanceExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroMaintenanceExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());

        self::assertSame(['options' => []], $container->getParameter('oro_maintenance.driver'));
        self::assertNull($container->getParameter('oro_maintenance.authorized.path'));
        self::assertNull($container->getParameter('oro_maintenance.authorized.host'));
        self::assertSame([], $container->getParameter('oro_maintenance.authorized.ips'));
        self::assertSame([], $container->getParameter('oro_maintenance.authorized.query'));
        self::assertSame([], $container->getParameter('oro_maintenance.authorized.cookie'));
        self::assertNull($container->getParameter('oro_maintenance.authorized.route'));
        self::assertSame([], $container->getParameter('oro_maintenance.authorized.attributes'));
        self::assertSame(503, $container->getParameter('oro_maintenance.response.http_code'));
        self::assertEquals(
            'Service Temporarily Unavailable',
            $container->getParameter('oro_maintenance.response.http_status')
        );
        self::assertEquals(
            'Service Temporarily Unavailable',
            $container->getParameter('oro_maintenance.response.exception_message')
        );
    }
}
