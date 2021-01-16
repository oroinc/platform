<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\TranslationBundle\DependencyInjection\OroTranslationExtension;

class OroTranslationExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $expectedDefinitions = [
            'oro_translation.form.type.translatable_entity',
            'oro_translation.form.type.select2_translatable_entity',
            'oro_translation.controller',
        ];

        $expectedParameters = [
            'translator.class',
            'oro_translation.js_translation.domains',
            'oro_translation.js_translation.debug',
            'oro_translation.debug_translator',
            'oro_translation.locales',
            'oro_translation.default_required',
            'oro_translation.templating',
        ];

        $expectedConfigValues = [
            'oro_translation' => [[
                'settings' => [
                    'resolved' => true,
                    'installed_translation_meta' => [
                        'value' => [],
                        'scope' => 'app',
                    ],
                ],
            ]],
        ];

        $this->loadExtension(new OroTranslationExtension());
        $this->assertDefinitionsLoaded($expectedDefinitions);
        $this->assertParametersLoaded($expectedParameters);
        $this->assertExtensionConfigsLoaded(['oro_translation'], $expectedConfigValues);
    }
}
