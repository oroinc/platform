<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DictionaryControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testSearch()
    {
        $expectedJson = '{"results":[{"id":"UM","value":"UM","text":"United States Minor Outlying Islands"},
        {"id":"US","value":"US","text":"United States"}]}';

        $this->client->request(
            'POST',
            $this->getUrl('oro_dictionary_search', ['dictionary' => 'Oro_Bundle_AddressBundle_Entity_Country']),
            ['q' => 'United States']
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $this->assertJsonStringEqualsJsonString($expectedJson, $result->getContent());
    }

    public function testLoadValue()
    {
        $expectedJson = '{"results":[{"id":"UM","value":"UM","text":"United States Minor Outlying Islands"},
        {"id":"US","value":"US","text":"United States"}]}';

        $this->client->request(
            'POST',
            $this->getUrl('oro_dictionary_value', ['dictionary'=>'Oro_Bundle_AddressBundle_Entity_Country']),
            [
                'keys'=>['US','UM']
            ]
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $this->assertJsonStringEqualsJsonString($expectedJson, $result->getContent());
    }
}
