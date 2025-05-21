<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\LayoutBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testProcessConfiguration(): void
    {
        $expected = [
            'view' => ['attributes' => true],
            'enabled_themes' => [],
            'templating' => [
                'default' => 'twig',
                'twig' => [
                    'resources' => ['@OroLayout/Layout/div_layout.html.twig']
                ]
            ],
            'debug' => '%kernel.debug%',
            'inherited_theme_options' => [],
        ];

        $processedConfig = (new Processor())->processConfiguration(new Configuration(), []);
        unset($processedConfig['settings']);

        self::assertSame($expected, $processedConfig);
    }
}
