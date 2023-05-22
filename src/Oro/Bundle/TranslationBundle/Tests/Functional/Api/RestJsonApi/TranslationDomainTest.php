<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TranslationBundle\Tests\Functional\Api\DataFixtures\LoadTranslations;
use Symfony\Component\HttpFoundation\Response;

class TranslationDomainTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadTranslations::class]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'translationdomains']);
        $responseContent = self::jsonToArray($response->getContent());
        $filteredItems = array_filter($responseContent['data'], function (array $item) {
            return \in_array($item['id'], ['messages', 'test_domain'], true);
        });
        usort($filteredItems, function (array $a, array $b) {
            return $a['id'] <=> $b['id'];
        });
        self::assertEquals(
            [
                [
                    'type'       => 'translationdomains',
                    'id'         => 'messages',
                    'attributes' => [
                        'description' => 'Default translation domain.'
                    ]
                ],
                [
                    'type'       => 'translationdomains',
                    'id'         => 'test_domain',
                    'attributes' => [
                        'description' => null
                    ]
                ],
            ],
            $filteredItems
        );
    }

    public function testTryToGet(): void
    {
        $response = $this->get(
            ['entity' => 'translationdomains', 'id' => 'messages'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'translationdomains'],
            ['data' => ['type' => 'translationdomains']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'translationdomains', 'id' => 'messages'],
            ['data' => ['type' => 'translationdomains', 'id' => 'messages']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'translationdomains', 'id' => 'messages'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'translationdomains'],
            ['filter' => ['id' => 'messages']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
