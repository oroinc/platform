<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Translation;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadStrategyLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\Stub\Strategy\TranslationStrategy;
use Oro\Bundle\TranslationBundle\Translation\Translator;

/**
 * @dbIsolation
 */
class TranslatorTest extends WebTestCase
{
    /** @var Translator */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader(), true);
        $this->loadFixtures([LoadStrategyLanguages::class]);

        $this->translator = $this->getContainer()->get('translator.default');
    }

    public function testRebuildCache()
    {
        $provider = new TranslationStrategyProvider();
        $this->getContainer()->set('oro_translation.strategy.provider', $provider);

        $provider->addStrategy(
            new TranslationStrategy('strategy1', ['lang1' => [], 'lang2' => [], 'lang3' => [], 'lang4' => []])
        );
        $provider->addStrategy(
            new TranslationStrategy('strategy2', ['lang2' => ['lang3' => ['lang4' => ['lang1' => []]]]])
        );
        $provider->addStrategy(
            new TranslationStrategy('strategy3', ['lang3' => [], 'lang4' => ['lang1' => ['lang2' => []]]])
        );

        // build initial cache
        $this->translator->rebuildCache();

        $key = uniqid('TRANSLATION_KEY_', true);
        $val1 = uniqid('TEST_VALUE1_', true);
        $val2 = uniqid('TEST_VALUE2_', true);

        /* @var $manager TranslationManager */
        $manager = $this->getContainer()->get('oro_translation.manager.translation');
        $manager->saveTranslation($key, $val1, 'lang1', TranslationManager::DEFAULT_DOMAIN, Translation::SCOPE_UI);
        $manager->saveTranslation($key, $val2, 'lang2', TranslationManager::DEFAULT_DOMAIN, Translation::SCOPE_UI);
        $manager->flush();
        $manager->clear();

        // Ensure that catalog still contains old translated values
        $provider->selectStrategy('strategy1');
        $this->assertTranslationEquals($key, ['lang1' => $key, 'lang2' => $key, 'lang3' => $key, 'lang4' => $key]);

        $provider->selectStrategy('strategy2');
        $this->assertTranslationEquals($key, ['lang1' => $key, 'lang2' => $key, 'lang3' => $key, 'lang4' => $key]);

        $provider->selectStrategy('strategy3');
        $this->assertTranslationEquals($key, ['lang1' => $key, 'lang2' => $key, 'lang3' => $key, 'lang4' => $key]);

        $this->translator->rebuildCache();

        // Ensure that catalog still contains new translated values
        $provider->selectStrategy('strategy1');
        $this->assertTranslationEquals($key, ['lang1' => $val1, 'lang2' => $val2, 'lang3' => $key, 'lang4' => $key]);

        $provider->selectStrategy('strategy2');
        $this->assertTranslationEquals($key, ['lang1' => $val1, 'lang2' => $val2, 'lang3' => $val2, 'lang4' => $val2]);

        $provider->selectStrategy('strategy3');
        $this->assertTranslationEquals($key, ['lang1' => $val1, 'lang2' => $val2, 'lang3' => $key, 'lang4' => $key]);
    }

    /**
     * @param string $key
     * @param array $items
     * @param string $domain
     */
    protected function assertTranslationEquals($key, array $items, $domain = TranslationManager::DEFAULT_DOMAIN)
    {
        $actualData = [];
        $expectedData = [];

        foreach ($items as $language => $expectedValue) {
            $actualData[$language] = $this->translator->trans($key, [], $domain, $language);
            $expectedData[$language] = $expectedValue;
        }

        $this->assertEquals($expectedData, $actualData);
    }
}
