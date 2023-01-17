<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;
use Oro\Bundle\TranslationBundle\Translation\Translator;

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

    public function testGetListWithTotalCount(): void
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

    public function testGetListWithoutTotalCount(): void
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
    public function testPatchAction(
        string $locale,
        ?string $inputValue,
        bool $expectedStatus,
        ?string $expectedValue,
        array $expectedFields
    ): void {
        $this->client->jsonRequest(
            'PATCH',
            $this->getUrl('oro_api_patch_translation', [
                'locale' => $locale,
                'domain' => LoadTranslations::TRANSLATION_KEY_DOMAIN,
                'key'    => LoadTranslations::TRANSLATION1
            ]),
            ['value' => $inputValue]
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $translation = $this->getRepository()->findTranslation(
            LoadTranslations::TRANSLATION1,
            $locale,
            LoadTranslations::TRANSLATION_KEY_DOMAIN
        );

        $this->assertSame(
            [
                'status' => $expectedStatus,
                'id'     => $translation?->getId(),
                'value'  => $expectedValue,
                'fields' => $expectedFields
            ],
            $result
        );
    }

    public function patchActionProvider(): array
    {
        return [
            'update value'            => [
                'locale'         => LoadLanguages::LANGUAGE1,
                'input'          => 'value1',
                'expectedStatus' => true,
                'expectedValue'  => 'value1',
                'expectedFields' => []
            ],
            'space value'             => [
                'locale'         => LoadLanguages::LANGUAGE1,
                'input'          => ' ',
                'expectedStatus' => true,
                'expectedValue'  => ' ',
                'expectedFields' => []
            ],
            'empty string value'      => [
                'locale'         => LoadLanguages::LANGUAGE1,
                'input'          => '',
                'expectedStatus' => false,
                'expectedValue'  => null,
                'expectedFields' => []
            ],
            'null value'              => [
                'locale'         => LoadLanguages::LANGUAGE1,
                'input'          => null,
                'expectedStatus' => false,
                'expectedValue'  => null,
                'expectedFields' => []
            ],
            'update value (EN)'       => [
                'locale'         => Translator::DEFAULT_LOCALE,
                'input'          => 'value1',
                'expectedStatus' => true,
                'expectedValue'  => 'value1',
                'expectedFields' => ['englishValue' => 'value1']
            ],
            'space value (EN)'        => [
                'locale'         => Translator::DEFAULT_LOCALE,
                'input'          => ' ',
                'expectedStatus' => true,
                'expectedValue'  => ' ',
                'expectedFields' => ['englishValue' => ' ']
            ],
            'empty string value (EN)' => [
                'locale'         => Translator::DEFAULT_LOCALE,
                'input'          => '',
                'expectedStatus' => true,
                'expectedValue'  => '',
                'expectedFields' => ['englishValue' => '']
            ],
            'null value (EN)'         => [
                'locale'         => Translator::DEFAULT_LOCALE,
                'input'          => null,
                'expectedStatus' => false,
                'expectedValue'  => null,
                'expectedFields' => ['englishValue' => 'translation.trans1']
            ],
        ];
    }
}
