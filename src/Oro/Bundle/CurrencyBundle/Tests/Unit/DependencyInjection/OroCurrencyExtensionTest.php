<?php
declare(strict_types=1);

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CurrencyBundle\DependencyInjection\OroCurrencyExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroCurrencyExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroCurrencyExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'default_currency' => ['value' => 'USD', 'scope' => 'app'],
                        'currency_display' => ['value' => 'symbol', 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_currency')
        );
    }
}
