<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Controller;

use Doctrine\Common\Persistence\ManagerRegistry;
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
    const DATAGRID_NAME = 'oro-translation-translations-grid';
    const RESET_ACTION_NAME = 'oro_translation_translation_reset';

    /** @var  ManagerRegistry */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadTranslations::class]);

        $this->registry = $this->getContainer()->get('doctrine');
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_translation_translation_index'));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains(self::DATAGRID_NAME, $crawler->html());

        $domains = $this->registry->getManagerForClass(TranslationKey::class)
            ->getRepository(TranslationKey::class)
            ->findAvailableDomains();

        // Assert Domain filter choices
        foreach ($domains as $domain) {
            $json = sprintf('{"label":"%s","value":"%s"}', $domain, $domain);
            $this->assertContains($json, $crawler->html(), 'JSON not found in page content');
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

    public function testMassReset()
    {
        $ids = [
            $this->getReference(LoadTranslations::TRANSLATION1)->getId(),
            $this->getReference(LoadTranslations::TRANSLATION2)->getId(),
        ];

        $this->client->request('POST', $this->getUrl('oro_translation_mass_reset', [
            'gridName' => self::DATAGRID_NAME,
            'actionName' => self::RESET_ACTION_NAME,
            'inset' => 1,
            'values' => ',,,' . implode(',', $ids) . ',,,',
        ]));

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);
        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($result['successful']);
        $this->assertArrayHasKey('count', $result);
        $this->assertEquals(2, $result['count']);


        /** @var TranslationRepository $repo */
        $repo = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityRepository(Translation::class);

        $translations = $repo->findBy(['id' => $ids]);
        $this->assertEmpty($translations);
    }

    public function testMassResetError()
    {
        $this->client->request('POST', $this->getUrl('oro_translation_mass_reset', [
            'gridName' => self::DATAGRID_NAME,
            'actionName' => self::RESET_ACTION_NAME,
            'inset' => 1,
            'values' => '',
        ]));

        $translator = $this->getContainer()->get('translator');

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);
        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('successful', $result);
        $this->assertFalse($result['successful']);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals(
            $translator->trans('oro.translation.action.reset.nothing_to_reset'),
            $result['message']
        );
    }

    /**
     * @param array $filter
     * @param array $gridOptions
     * @return string
     */
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
