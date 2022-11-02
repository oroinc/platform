<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;
use Symfony\Component\HttpFoundation\Response;

class TranslationControllerTest extends WebTestCase
{
    private const DATAGRID_NAME = 'oro-translation-translations-grid';
    private const RESET_ACTION_NAME = 'oro_translation_translation_reset';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadTranslations::class]);
    }

    private function getDoctrine(): ManagerRegistry
    {
        return $this->getContainer()->get('doctrine');
    }

    private function getTranslations(string $locale, string $domain): array
    {
        return $this->getDoctrine()->getRepository(Translation::class)->createQueryBuilder('t')
            ->distinct(true)
            ->select('t.id, t.value, k.key, k.domain, l.code')
            ->join('t.language', 'l')
            ->join('t.translationKey', 'k')
            ->where('l.code = :code AND k.domain = :domain AND t.scope > :scope')
            ->setParameter('code', $locale)
            ->setParameter('domain', $domain)
            ->setParameter('scope', Translation::SCOPE_SYSTEM)
            ->getQuery()
            ->getArrayResult();
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_translation_translation_index'));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString(self::DATAGRID_NAME, $crawler->html());

        $domains = $this->getDoctrine()->getRepository(TranslationKey::class)
            ->findAvailableDomains();

        // Assert Domain filter choices
        $gridElement = $crawler->filter('[data-page-component-name="oro-translation-translations-grid"]');
        $gridComponentOptions = json_decode(
            $gridElement->attr('data-page-component-options'),
            true,
            513,
            JSON_THROW_ON_ERROR
        );
        $this->assertArrayHasKey('metadata', $gridComponentOptions);
        $this->assertArrayHasKey('filters', $gridComponentOptions['metadata']);
        $this->assertIsArray($gridComponentOptions['metadata']['filters']);
        $domainFilter = null;
        foreach ($gridComponentOptions['metadata']['filters'] as $filter) {
            if ($filter['name'] === 'domain') {
                $domainFilter = $filter;
                break;
            }
        }
        foreach ($domains as $domain) {
            self::assertContains(['label' => $domain, 'value' => $domain], $domainFilter['choices']);
        }

        $language = $this->getDoctrine()->getRepository(Language::class)
            ->findOneBy(['code' => LoadLanguages::LANGUAGE1]);
        $response = $this->getDatagridJsonResponse(
            [
                self::DATAGRID_NAME . '[_filter][domain][value][]' => LoadTranslations::TRANSLATION_KEY_DOMAIN,
                self::DATAGRID_NAME . '[_filter][language][value][]' => $language->getId(),
                self::DATAGRID_NAME . '[_filter][status][value][]' => 1,
            ]
        );

        $translations = $this->getTranslations(LoadLanguages::LANGUAGE1, LoadTranslations::TRANSLATION_KEY_DOMAIN);
        foreach ($translations as $translation) {
            self::assertStringContainsString(sprintf('"id":"%d"', $translation['id']), $response);
            self::assertStringContainsString(sprintf('"key":"%s"', $translation['key']), $response);
            self::assertStringContainsString(sprintf('"domain":"%s"', $translation['domain']), $response);
            self::assertStringContainsString(sprintf('"value":"%s"', $translation['value']), $response);
        }
    }

    public function testMassReset()
    {
        $ids = [
            $this->getReference(LoadTranslations::TRANSLATION1)->getId(),
            $this->getReference(LoadTranslations::TRANSLATION2)->getId(),
        ];

        $this->ajaxRequest('POST', $this->getUrl('oro_translation_mass_reset', [
            'gridName' => self::DATAGRID_NAME,
            'actionName' => self::RESET_ACTION_NAME,
            'inset' => 1,
            'values' => ',,,' . implode(',', $ids) . ',,,',
        ]));

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);
        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotEmpty($result['successful']);
        $this->assertArrayHasKey('count', $result);
        $this->assertEquals(2, $result['count']);

        /** @var TranslationRepository $repo */
        $repo = $this->getDoctrine()->getRepository(Translation::class);

        $translations = $repo->findBy(['id' => $ids]);
        $this->assertEmpty($translations);
    }

    public function testMassResetError()
    {
        $this->ajaxRequest('POST', $this->getUrl('oro_translation_mass_reset', [
            'gridName' => self::DATAGRID_NAME,
            'actionName' => self::RESET_ACTION_NAME,
            'inset' => 1,
            'values' => '',
        ]));

        $translator = $this->getContainer()->get('translator');

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);
        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('successful', $result);
        $this->assertFalse($result['successful']);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals(
            $translator->trans('oro.translation.action.reset.nothing_to_reset'),
            $result['message']
        );
    }

    private function getDatagridJsonResponse(array $filter = [], array $gridOptions = []): string
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
