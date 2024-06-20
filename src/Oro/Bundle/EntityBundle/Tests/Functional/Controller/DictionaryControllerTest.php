<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DictionaryControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    public function testGetValuesBySearchQuery(): void
    {
        $expectedJson = '{"results":['
            . '{"id":"UM","value":"UM","text":"United States Minor Outlying Islands"},'
            . '{"id":"US","value":"US","text":"United States"}]}';

        $this->client->request(
            'POST',
            $this->getUrl('oro_dictionary_search', ['dictionary' => 'Oro_Bundle_AddressBundle_Entity_Country']),
            ['q' => 'United States']
        );
        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);
        $this->assertJsonStringEqualsJsonString($expectedJson, $result->getContent());
    }

    public function testGetValuesByIds(): void
    {
        $expectedJson = '{"results":['
            . '{"id":"UM","value":"UM","text":"United States Minor Outlying Islands"},'
            . '{"id":"US","value":"US","text":"United States"}]}';

        $this->client->request(
            'POST',
            $this->getUrl('oro_dictionary_value', ['dictionary' => 'Oro_Bundle_AddressBundle_Entity_Country']),
            ['keys' => ['US', 'UM']]
        );
        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);
        $this->assertJsonStringEqualsJsonString($expectedJson, $result->getContent());
    }

    public function testGetValuesByIdsWhenProvidedIdsIsEmptyString(): void
    {
        $expectedJson = '{"results":[]}';

        $this->client->request(
            'POST',
            $this->getUrl('oro_dictionary_value', ['dictionary' => 'Oro_Bundle_AddressBundle_Entity_Country']),
            ['keys' => '']
        );
        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);
        $this->assertJsonStringEqualsJsonString($expectedJson, $result->getContent());
    }
}
