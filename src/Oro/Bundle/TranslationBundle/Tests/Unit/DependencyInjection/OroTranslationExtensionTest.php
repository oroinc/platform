<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TranslationBundle\DependencyInjection\OroTranslationExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroTranslationExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $configs = [
            [
                'translation_service' => [
                    'apikey' => 'test_api_key'
                ],
                'package_names' => ['package1', 'package2', 'package1'],
                'locales' => ['en', 'fr'],
                'translatable_dictionaries' => [
                    'Test\TranslatableDictionary' => [
                        'label' => ['translation_key_prefix' => 'entity.', 'key_field_name' => 'name']
                    ]
                ]
            ]
        ];

        $extension = new OroTranslationExtension();
        $extension->load($configs, $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'installed_translation_meta' => ['value' => [], 'scope' => 'app']
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_translation')
        );

        self::assertEquals(
            ['jsmessages', 'validators'],
            $container->getDefinition('oro_translation.js_generator')->getArgument('$domains')
        );
        self::assertEquals(
            ['jsmessages', 'validators'],
            $container->getDefinition('oro_translation.manager.translation')->getArgument('$jsTranslationDomains')
        );
        self::assertTrue(
            $container->getDefinition('oro_translation.twig.translation.extension')
                ->getArgument('$isDebugJsTranslations')
        );

        self::assertSame('test_api_key', $container->getParameter('oro_translation.translation_service.apikey'));
        self::assertSame(['package1', 'package2'], $container->getParameter('oro_translation.package_names'));
        self::assertFalse($container->getParameter('oro_translation.debug_translator'));
        self::assertSame(['en', 'fr'], $container->getParameter('oro_translation.locales'));
        self::assertTrue($container->getParameter('oro_translation.default_required'));
        self::assertEquals(
            '@OroTranslation/default.html.twig',
            $container->getParameter('oro_translation.templating')
        );

        self::assertEquals(
            [
                ['addEntity', ['Test\TranslatableDictionary', 'label', 'entity.', 'name']]
            ],
            $container->getDefinition('oro_translation.event_listener.update_translatable_dictionaries')
                ->getMethodCalls()
        );
    }
}
