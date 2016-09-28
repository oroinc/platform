<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;

/**
 * @dbIsolation
 */
class LanguageControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadLanguages::class]);
    }

    public function testIndexGeneral()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_translation_language_index'));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('oro-translation-language-grid', $crawler->html());

        $languages = $this->getContainer()->get('oro_translation.provider.language')->getAvailableLanguages();

        foreach ($languages as $language) {
            $this->assertContains(sprintf('"language":"%s"', $language), $crawler->html());
        }
    }
}
