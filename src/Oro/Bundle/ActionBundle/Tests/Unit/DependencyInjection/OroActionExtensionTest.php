<?php
declare(strict_types=1);

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ActionBundle\DependencyInjection\OroActionExtension;
use Oro\Bundle\ActionBundle\OroActionBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroActionExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroActionExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }

    public function testPrependForProdEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroActionExtension();
        $extension->prepend($container);

        self::assertEquals([], $container->getExtensionConfig('twig'));
    }

    public function testPrependForTestEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension = new OroActionExtension();
        $extension->prepend($container);

        self::assertEquals(
            [
                ['paths' => [(new OroActionBundle())->getPath() . '/Tests/Functional/Stub/views' => 'OroActionStub']]
            ],
            $container->getExtensionConfig('twig')
        );
    }
}
