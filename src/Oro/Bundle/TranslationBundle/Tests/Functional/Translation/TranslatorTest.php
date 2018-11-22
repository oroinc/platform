<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Translation;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadStrategyLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\Stub\Strategy\TranslationStrategy;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\Config\ConfigCacheFactory;
use Symfony\Component\Config\ResourceCheckerConfigCacheFactory;
use Symfony\Component\Yaml\Yaml;

/**
 * @dbIsolationPerTest
 */
class TranslatorTest extends WebTestCase
{
    /** @var TranslationStrategyProvider */
    protected $provider;

    /** @var array */
    protected $resources;

    /** @var TranslationStrategy[] */
    protected $strategies;

    /** @var Translator */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadStrategyLanguages::class]);

        $this->translator = $this->getContainer()->get('translator.default');

        $this->createStrategies();

        $this->provider = new TranslationStrategyProvider();
        self::getContainer()->set('oro_translation.strategy.provider', $this->provider);
        // Update translation strategy provider's service link for translator
        // to ensure that old strategy provider is not in local cache
        $this->translator->setStrategyProviderLink(new ServiceLink(
            self::getContainer(),
            'oro_translation.strategy.provider'
        ));

        foreach ($this->strategies as $strategy) {
            $this->provider->addStrategy($strategy);
        }

        $cacheDir = $this->getContainer()->getParameter('kernel.cache_dir') . DIRECTORY_SEPARATOR . 'translations';
        $this->resources['lang1'] = $cacheDir . DIRECTORY_SEPARATOR . 'messages.lang1.yml';
        $this->resources['lang2'] = $cacheDir . DIRECTORY_SEPARATOR . 'messages.lang2.yml';
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        foreach ($this->resources as $resource) {
            if (is_file($resource)) {
                unlink($resource);
            }
        }
    }

    public function testWarmUp()
    {
        // select dynamic cache factory
        $this->translator->setConfigCacheFactory(new ConfigCacheFactory(true));

        $key = uniqid('TRANSLATION_KEY_', true);
        $val1 = uniqid('TEST_VALUE1_', true);
        $val2 = uniqid('TEST_VALUE2_', true);

        foreach ($this->resources as $language => $resource) {
            $this->writeResource($language, []);
            $this->translator->addResource('yml', $resource, $language, TranslationManager::DEFAULT_DOMAIN);
        }

        // build initial cache
        $this->translator->rebuildCache();

        // Ensure that catalog still contains translation keys
        $this->provider->setStrategy($this->getStrategy('strategy1'));
        $this->assertTranslationEquals($key, ['lang1' => $key, 'lang2' => $key, 'lang3' => $key, 'lang4' => $key]);

        $this->provider->setStrategy($this->getStrategy('strategy2'));
        $this->assertTranslationEquals($key, ['lang1' => $key, 'lang2' => $key, 'lang3' => $key, 'lang4' => $key]);

        $this->provider->setStrategy($this->getStrategy('strategy3'));
        $this->assertTranslationEquals($key, ['lang1' => $key, 'lang2' => $key, 'lang3' => $key, 'lang4' => $key]);

        // update resources and warmUp cache
        $this->writeResource('lang1', [$key => $val1], time() + 3600);
        $this->writeResource('lang2', [$key => $val2], time() + 3600);

        $this->provider->setStrategy($this->getStrategy('strategy2'));
        $this->translator->warmUp('');

        // revert original cache factory
        $this->translator->setConfigCacheFactory(new ResourceCheckerConfigCacheFactory());

        // Ensure that catalog still contains translation keys
        $this->provider->setStrategy($this->getStrategy('strategy1'));
        $this->assertTranslationEquals($key, ['lang1' => $key, 'lang2' => $key, 'lang3' => $key, 'lang4' => $key]);

        // Ensure that catalog still contains new translated values
        $this->provider->setStrategy($this->getStrategy('strategy2'));
        $this->assertTranslationEquals($key, ['lang1' => $val1, 'lang2' => $val2, 'lang3' => $val2, 'lang4' => $val2]);

        // Ensure that catalog still contains translation keys
        $this->provider->setStrategy($this->getStrategy('strategy3'));
        $this->assertTranslationEquals($key, ['lang1' => $key, 'lang2' => $key, 'lang3' => $key, 'lang4' => $key]);
    }

    public function testRebuildCache()
    {
        // build initial cache
        $this->translator->rebuildCache();
        $this->assertEquals(['lang1', 'lang2', 'lang3', 'lang4'], $this->translator->getFallbackLocales());

        $key = uniqid('TRANSLATION_KEY_', true);
        $val1 = uniqid('TEST_VALUE1_', true);
        $val2 = uniqid('TEST_VALUE2_', true);

        /** @var $manager TranslationManager */
        $manager = $this->getContainer()->get('oro_translation.manager.translation');
        $manager->saveTranslation($key, $val1, 'lang1', TranslationManager::DEFAULT_DOMAIN, Translation::SCOPE_UI);
        $manager->saveTranslation($key, $val2, 'lang2', TranslationManager::DEFAULT_DOMAIN, Translation::SCOPE_UI);
        $manager->flush();
        $manager->clear();

        // Ensure that catalog still contains old translated values
        $this->provider->setStrategy($this->getStrategy('strategy1'));
        $this->assertTranslationEquals($key, ['lang1' => $key, 'lang2' => $key, 'lang3' => $key, 'lang4' => $key]);

        $this->provider->setStrategy($this->getStrategy('strategy2'));
        $this->assertTranslationEquals($key, ['lang1' => $key, 'lang2' => $key, 'lang3' => $key, 'lang4' => $key]);

        $this->provider->setStrategy($this->getStrategy('strategy3'));
        $this->assertTranslationEquals($key, ['lang1' => $key, 'lang2' => $key, 'lang3' => $key, 'lang4' => $key]);

        $this->translator->rebuildCache();

        // Ensure that catalog still contains new translated values
        $this->provider->setStrategy($this->getStrategy('strategy1'));
        $this->assertTranslationEquals($key, ['lang1' => $val1, 'lang2' => $val2, 'lang3' => $key, 'lang4' => $key]);

        $this->provider->setStrategy($this->getStrategy('strategy2'));
        $this->assertTranslationEquals($key, ['lang1' => $val1, 'lang2' => $val2, 'lang3' => $val2, 'lang4' => $val2]);

        $this->provider->setStrategy($this->getStrategy('strategy3'));
        $this->assertTranslationEquals($key, ['lang1' => $val1, 'lang2' => $val2, 'lang3' => $key, 'lang4' => $key]);
    }

    /**
     * @param string $name
     * @return TranslationStrategy
     */
    protected function getStrategy($name)
    {
        return $this->strategies[$name];
    }

    protected function createStrategies()
    {
        $this->strategies['strategy1'] = new TranslationStrategy('strategy1', [
            'lang1' => [],
            'lang2' => [],
            'lang3' => [],
            'lang4' => [],
        ]);

        $this->strategies['strategy2'] = new TranslationStrategy('strategy2', [
            'lang2' => [
                'lang3' => [
                    'lang4' => [
                        'lang1' => [
                        ],
                    ],
                ],
            ],
        ]);

        $this->strategies['strategy3'] = new TranslationStrategy('strategy3', [
            'lang3' => [],
            'lang4' => [
                'lang1' => [
                    'lang2' => [],
                ],
            ],
        ]);
    }

    /**
     * @param string $resourceName
     * @param array $data
     * @param int|null $timestamp
     */
    protected function writeResource($resourceName, array $data, $timestamp = null)
    {
        $fileName = $this->resources[$resourceName];

        file_put_contents($fileName, Yaml::dump($data));
        touch($fileName, $timestamp ?: time());
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
