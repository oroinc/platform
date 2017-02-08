<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslationUsers;

class LanguageControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader(
            LoadTranslationUsers::TRANSLATOR_USERNAME,
            LoadTranslationUsers::TRANSLATOR_USERNAME
        ));
        $this->loadFixtures([LoadLanguages::class]);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_translation_language_index'));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('oro-translation-language-grid', $crawler->html());

        $languages = $this->getContainer()->get('oro_translation.provider.language')->getAvailableLanguages();

        $result = $this->getJsonResponseContent($this->client->requestGrid('oro-translation-language-grid'), 200);
        $this->assertCount(1, $result['data']);

        $data = array_shift($result['data']);

        $this->assertArrayHasKey('language', $data);
        $this->assertEquals($languages[LoadLanguages::LANGUAGE3], $data['language']);
    }
}
