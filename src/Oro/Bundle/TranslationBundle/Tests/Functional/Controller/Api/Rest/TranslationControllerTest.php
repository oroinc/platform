<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

class TranslationControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([LoadTranslations::class]);
    }

    private function getRepository(): TranslationRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(Translation::class);
    }

    public function testGetListWithTotalCount()
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl(
                'oro_api_get_translations',
                ['domain' => 'validators']
            ),
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
        $this->client->jsonRequest('GET', $this->getUrl('oro_api_get_translations', ['domain' => 'validators']));

        $response = $this->client->getResponse();
        $result = $this->getJsonResponseContent($response, 200);
        $this->assertNotEmpty($result);

        $this->assertFalse(
            $response->headers->has('X-Include-Total-Count'),
            'Response headers should not have X-Include-Total-Count'
        );
    }

    /**
     * @dataProvider patchActionProvider
     */
    public function testPatchAction(?string $inputValue, ?string $expectedValue, bool $expectedStatus)
    {
        $this->client->jsonRequest(
            'PATCH',
            $this->getUrl('oro_api_patch_translation', [
                'locale' => LoadLanguages::LANGUAGE1,
                'domain' => LoadTranslations::TRANSLATION_KEY_DOMAIN,
                'key' => LoadTranslations::TRANSLATION1,
            ]),
            ['value' => $inputValue]
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $translation = $this->getRepository()->findTranslation(
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

    public function patchActionProvider(): array
    {
        return [
            'update value' => [
                'input' => 'value1',
                'expectedValue' => 'value1',
                'expectedStatus' => true,
            ],
            'empty string value' => [
                'input' => '',
                'expectedValue' => '',
                'expectedStatus' => true,
            ],
            'null value' => [
                'input' => null,
                'expectedValue' => null,
                'expectedStatus' => false,
            ],
        ];
    }
}
