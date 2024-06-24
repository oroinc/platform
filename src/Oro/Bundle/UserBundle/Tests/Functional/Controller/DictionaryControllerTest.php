<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DictionaryControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    public function testGetValuesForUserBySearchQuery(): void
    {
        $expectedJson = '{"results":[{"id":1,"value":1,"text":"John Doe"}]}';

        $this->client->request(
            'POST',
            $this->getUrl('oro_dictionary_search', ['dictionary' => 'Oro_Bundle_UserBundle_Entity_User']),
            ['q' => 'doe']
        );
        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);
        $this->assertJsonStringEqualsJsonString($expectedJson, $result->getContent());
    }

    public function testGetValuesForUserByIds(): void
    {
        $expectedJson = '{"results":[{"id":1,"value":1,"text":"John Doe"}]}';

        $this->client->request(
            'POST',
            $this->getUrl('oro_dictionary_value', ['dictionary' => 'Oro_Bundle_UserBundle_Entity_User']),
            ['keys' => [1]]
        );
        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);
        $this->assertJsonStringEqualsJsonString($expectedJson, $result->getContent());
    }
}
