<?php
declare(strict_types=1);

namespace Oro\Bundle\UIBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\UIBundle\DependencyInjection\OroUIExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class OroUIExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadDefaultConfiguration()
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.environment' => 'prod'
        ]));

        $extension = new OroUIExtension();
        $extension->load([], $container);

        self::assertEquals(
            [
                [
                    'settings' => [
                        'resolved'          => true,
                        'organization_name' => ['value' => 'ORO', 'scope' => 'app'],
                        'application_url'   => ['value' => 'http://localhost', 'scope' => 'app'],
                        'navbar_position'   => ['value' => 'left', 'scope' => 'app']
                    ]
                ]
            ],
            $container->getExtensionConfig($extension->getAlias())
        );
    }
}
