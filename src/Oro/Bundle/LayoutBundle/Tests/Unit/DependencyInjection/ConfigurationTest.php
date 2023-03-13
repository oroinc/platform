<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\LayoutBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessConfiguration(): void
    {
        $expected = [
            'view' => ['annotations' => true],
            'templating' => [
                'default' => 'twig',
                'twig' => [
                    'resources' => ['@OroLayout/Layout/div_layout.html.twig']
                ]
            ],
            'debug' => '%kernel.debug%',
            'enabled_themes' => []
        ];

        $processedConfig = (new Processor())->processConfiguration(new Configuration(), []);
        unset($processedConfig['settings']);
        self::assertEquals($expected, $processedConfig);
    }
}
