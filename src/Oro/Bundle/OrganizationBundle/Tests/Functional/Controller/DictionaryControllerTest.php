<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DictionaryControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    public function testGetValuesForBusinessUnitBySearchQuery(): void
    {
        $expectedJson = '{"results":[{"id":1,"value":1,"text":"Main"}]}';

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_dictionary_search',
                ['dictionary' => 'Oro_Bundle_OrganizationBundle_Entity_BusinessUnit']
            ),
            ['q' => 'main']
        );
        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);
        $this->assertJsonStringEqualsJsonString($expectedJson, $result->getContent());
    }

    public function testGetValuesForBusinessUnitByIds(): void
    {
        $expectedJson = '{"results":[{"id":1,"value":1,"text":"Main"}]}';

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_dictionary_value',
                ['dictionary' => 'Oro_Bundle_OrganizationBundle_Entity_BusinessUnit']
            ),
            ['keys' => [1]]
        );
        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);
        $this->assertJsonStringEqualsJsonString($expectedJson, $result->getContent());
    }
}
