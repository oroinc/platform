<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Translation\Translator;

/**
 * @dbIsolation
 */
class TranslationOperationsTest extends ActionTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader(), true);
        $this->loadFixtures([LoadLanguages::class]);
    }

    public function testUpdateCacheOperation()
    {
        $key = uniqid('TRANSLATION_KEY_', true);
        $locale = LoadLanguages::LANGUAGE1;
        $expectedValue = uniqid('TEST_VALUE_', true);

        /** @var Translator $translator */
        $translator = $this->getContainer()->get('translator');
        $translator->warmUp(null);

        /** @var TranslationManager $manager */
        $manager = $this->getContainer()->get('oro_translation.manager.translation');
        $manager->saveValue($key, $expectedValue, $locale, TranslationManager::DEFAULT_DOMAIN, Translation::SCOPE_UI);
        $manager->flush();

        $this->assertEquals($key, $translator->trans($key, [], TranslationManager::DEFAULT_DOMAIN, $locale));

        $this->assertExecuteOperation(
            'oro_translation_rebuild_cache',
            null,
            null,
            ['route' => 'oro_translation_translation_index']
        );

        $this->assertEquals($expectedValue, $translator->trans($key, [], TranslationManager::DEFAULT_DOMAIN, $locale));
    }
}
