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

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_translation_language_index'));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('oro-translation-language-grid', $crawler->html());
        $this->assertContains(LoadLanguages::LANGUAGE1_NAME, $crawler->html());
        $this->assertContains(LoadLanguages::LANGUAGE2_NAME, $crawler->html());
    }
}
