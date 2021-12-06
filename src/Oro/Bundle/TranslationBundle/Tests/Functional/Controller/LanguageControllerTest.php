<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslationUsers;
use Symfony\Component\Intl\Locales;

class LanguageControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader(
            LoadTranslationUsers::TRANSLATOR_USERNAME,
            LoadTranslationUsers::TRANSLATOR_USERNAME
        ));
        $this->loadFixtures([LoadLanguages::class]);
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_translation_language_index'));

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString('oro-translation-language-grid', $crawler->html());

        $languages = self::getContainer()->get('oro_translation.provider.language')
            ->getAvailableLanguagesByCurrentUser();
        $languagesCount = \count($languages);

        $result = self::getJsonResponseContent($this->client->requestGrid('oro-translation-language-grid'), 200);
        self::assertCount($languagesCount, $result['data']);

        for ($i = 0; $i < $languagesCount; $i ++) {
            self::assertEquals(Locales::getName($languages[$i]->getCode(), 'en'), $result['data'][$i]['language']);
        }
    }
}
