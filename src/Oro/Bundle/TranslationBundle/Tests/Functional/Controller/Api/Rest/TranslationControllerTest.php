<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

/**
 * @dbIsolation
 */
class TranslationControllerTest extends WebTestCase
{
    /** @var TranslationManager */
    protected $manager;

    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());

        $this->loadFixtures([LoadTranslations::class]);

        $this->manager = $this->getContainer()->get('oro_translation.manager.translation');
    }

    public function testGetListWithTotalCount()
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_get_translations',
                ['domain' => 'validators']
            ),
            [],
            [],
            ['HTTP_X-Include' => 'totalCount']
        );

        $response = $this->client->getResponse();
        $result = $this->getJsonResponseContent($response, 200);
        $this->assertNotEmpty($result);

        $this->assertTrue(
            $response->headers->has('X-Include-Total-Count'),
            'Response headers should have X-Include-Total-Count'
        );
        $this->assertGreaterThan(0, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListWithoutTotalCount()
    {
        $this->client->request('GET', $this->getUrl('oro_api_get_translations', ['domain' => 'validators']));

        $response = $this->client->getResponse();
        $result = $this->getJsonResponseContent($response, 200);
        $this->assertNotEmpty($result);

        $this->assertFalse(
            $response->headers->has('X-Include-Total-Count'),
            'Response headers should not have X-Include-Total-Count'
        );
    }

    /**
     * @param string|null $value
     *
     * @dataProvider patchActionProvider
     */
    public function testPatchAction($inputValue, $expectedValue, $expectedStatus)
    {
        $this->client->request(
            'PATCH',
            $this->getUrl('oro_api_patch_translation', [
                'locale' => LoadLanguages::LANGUAGE1,
                'domain' => LoadTranslations::TRANSLATION_KEY_DOMAIN,
                'key' => LoadTranslations::TRANSLATION1,
            ]),
            [],
            [],
            [],
            json_encode(['value' => $inputValue])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $translation = $this->manager->findValue(
            LoadTranslations::TRANSLATION1,
            LoadLanguages::LANGUAGE1,
            LoadTranslations::TRANSLATION_KEY_DOMAIN
        );

        $this->assertEquals(
            [
                'id' => $expectedStatus ? $translation->getId() : '',
                'value' => $expectedValue,
                'status' => $expectedStatus,
            ],
            $result
        );
    }

    /**
     * @return array
     */
    public function patchActionProvider()
    {
        return [
            'update value' => [
                'input' => 'value1',
                'expectedValue' => 'value1',
                'expectedStatus' => true,
            ],
            'delete value' => [
                'input' => '',
                'expectedValue' => '',
                'expectedStatus' => false,
            ],
        ];
    }
}
