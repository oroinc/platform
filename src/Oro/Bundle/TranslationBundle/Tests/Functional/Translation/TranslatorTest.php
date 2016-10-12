<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Translation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Translation\Translator;

/**
 * @dbIsolation
 */
class TranslatorTest extends ActionTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader(), true);
        $this->loadFixtures([LoadLanguages::class]);
    }

    public function testRebuildCache()
    {
        /** @var Translator $translator */
        $translator = $this->getContainer()->get('translator');

        // build initial cache
        $translator->rebuildCache();

        $key = uniqid('TRANSLATION_KEY_', true);
        $domain = TranslationManager::DEFAULT_DOMAIN;
        $locale = LoadLanguages::LANGUAGE2;
        $expectedValue = uniqid('TEST_VALUE_', true);

        /** @var TranslationManager $manager */
        $manager = $this->getContainer()->get('oro_translation.manager.translation');
        $manager->saveValue($key, $expectedValue, $locale, $domain, Translation::SCOPE_UI);

        $manager->flush();
        $manager->clear();

        // Ensure that catalog still contains old translated value
        $this->assertNotEquals($expectedValue, $translator->trans($key, [], $domain, $locale));

        $translator->rebuildCache();

        // Ensure that catalog now contains new translated value
        $this->assertEquals($expectedValue, $translator->trans($key, [], $domain, $locale));
    }
}
