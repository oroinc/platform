<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TranslationBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessConfiguration(): void
    {
        $processor = new Processor();
        $expected = [
            'settings' => [
                'resolved' => true,
                'installed_translation_meta' => [
                    'value' => [],
                    'scope' => 'app'
                ]
            ],
            'js_translation' => [
                'domains' => [
                    'jsmessages',
                    'validators'
                ],
                'debug' => true
            ],
            'translation_service' => [
                'apikey' => ''
            ],
            'package_names' => [],
            'debug_translator' => false,
            'locales' => [],
            'default_required' => true,
            'manager_registry' => 'doctrine',
            'templating' => 'OroTranslationBundle::default.html.twig'
        ];

        static::assertEquals(
            $expected,
            $processor->processConfiguration(
                new Configuration(),
                [
                    'oro_translation' => [
                        'js_translation' => [],
                        'translation_service' => []
                    ]
                ]
            )
        );
    }
}
