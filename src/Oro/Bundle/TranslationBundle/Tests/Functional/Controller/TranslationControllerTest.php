<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Controller;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolation
 */
class TranslationControllerTest extends WebTestCase
{
    const DATAGRID_ROUTE = 'oro_translation_translation_index';
    const DATAGRID_NAME = 'oro-translation-translations-grid';

    /** @var  ManagerRegistry */
    protected $registry;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadTranslations::class]);

        $this->registry = $this->getContainer()->get('doctrine');
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl(self::DATAGRID_ROUTE));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains(self::DATAGRID_NAME, $crawler->html());

        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);

        $domains = $this->registry->getManagerForClass(TranslationKey::class)
            ->getRepository(TranslationKey::class)
            ->findAvailableDomains();

        // Assert Domain filter choices
        foreach ($domains as $domain) {
            $this->assertContains(sprintf('{"label":"%s","value":"%s"}', $domain, $domain), $crawler->html());
        }

        $language = $this->registry->getRepository(Language::class)->findOneBy(['code' => LoadLanguages::LANGUAGE1]);
        $response = $this->getDatagridJsonResponse(
            [
                self::DATAGRID_NAME . '[_filter][domain][value][]' => LoadTranslations::TRANSLATION_KEY_DOMAIN,
                self::DATAGRID_NAME . '[_filter][language][value][]' => $language->getId(),
                self::DATAGRID_NAME . '[_filter][status][value][]' => 1,
            ]
        );

        $translations = $this->registry
            ->getRepository(Translation::class)
            ->findAllByLanguageAndDomain(LoadLanguages::LANGUAGE1, LoadTranslations::TRANSLATION_KEY_DOMAIN);

        foreach ($translations as $translation) {
            $this->assertContains(sprintf('"id":"%d"', $translation['id']), $response);
            $this->assertContains(sprintf('"key":"%s"', $translation['key']), $response);
            $this->assertContains(sprintf('"domain":"%s"', $translation['domain']), $response);
            $this->assertContains(sprintf('"value":"%s"', $translation['value']), $response);
        }
    }

    protected function getDatagridJsonResponse(array $filter = [], array $gridOptions = [])
    {
        $response = $this->client->requestGrid(
            array_merge(
                ['gridName' => self::DATAGRID_NAME],
                $gridOptions
            ),
            $filter,
            true
        );
        $this->assertJsonResponseStatusCodeEquals($response, Response::HTTP_OK);

        return $response->getContent();
    }
}
