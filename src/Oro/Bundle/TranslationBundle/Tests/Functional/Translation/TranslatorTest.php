<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Translation;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadStrategyLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\Stub\Strategy\TranslationStrategy;
use Oro\Bundle\TranslationBundle\Translation\Translator;

/**
 * @dbIsolationPerTest
 */
class TranslatorTest extends WebTestCase
{
    private Translator $translator;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadStrategyLanguages::class]);

        $this->translator = self::getContainer()->get('translator.default');
        $this->translator->setStrategyProvider(new TranslationStrategyProvider([$this->createStrategy()]));
    }

    private function getTranslationManager(): TranslationManager
    {
        return self::getContainer()->get('oro_translation.manager.translation');
    }

    private function createStrategy(): TranslationStrategyInterface
    {
        return new TranslationStrategy('strategy1', [
            'en' => [
                'lang1' => ['lang2' => []],
                'lang3' => ['lang4' => []]
            ]
        ]);
    }
    public function testTransForDynamicTranslations(): void
    {
        $key = uniqid('TRANSLATION_KEY_', true);
        $enValue = uniqid('TEST_VALUE1_', true);
        $lang1Value = uniqid('TEST_VALUE1_', true);

        $manager = $this->getTranslationManager();
        $manager->saveTranslation(
            $key,
            $enValue,
            'en',
            TranslationManager::DEFAULT_DOMAIN,
            Translation::SCOPE_INSTALLED
        );
        $manager->saveTranslation(
            $key,
            $lang1Value,
            'lang1',
            TranslationManager::DEFAULT_DOMAIN,
            Translation::SCOPE_INSTALLED
        );
        $manager->flush();

        // Check that translator returns correct translation for locale without translation cache.
        $this->assertEquals(
            $enValue,
            $this->translator->trans($key, [], TranslationManager::DEFAULT_DOMAIN, 'en'),
            'Translator must be able to load old EN catalogue and return correct EN translation.'
        );

        $this->assertEquals(
            $lang1Value,
            $this->translator->trans($key, [], TranslationManager::DEFAULT_DOMAIN, 'lang1'),
            'Translator must be able to load new LANG1 catalogue and return correct LANG1 translation.'
        );

        $this->assertEquals(
            $lang1Value,
            $this->translator->trans($key, [], TranslationManager::DEFAULT_DOMAIN, 'lang2'),
            'Translator must be able to load new LANG2 catalogue and return correct LANG1 translation.'
        );

        $this->assertEquals(
            $enValue,
            $this->translator->trans($key, [], TranslationManager::DEFAULT_DOMAIN, 'lang3'),
            'Translator must be able to load new LANG3 catalogue and return correct EN translation.'
        );

        $this->assertEquals(
            $enValue,
            $this->translator->trans($key, [], TranslationManager::DEFAULT_DOMAIN, 'lang4'),
            'Translator must be able to load new LANG4 catalogue and return correct EN translation.'
        );
    }
}
