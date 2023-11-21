<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Api\RestPlain;

use Oro\Bundle\ApiBundle\Tests\Functional\RestPlainApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class ConfigurationOptionTest extends RestPlainApiTestCase
{
    public function testGetList(): array
    {
        $response = $this->cget(['entity' => 'configurationoptions'], ['limit' => -1]);
        $requestInfo = 'get_list';
        self::assertApiResponseStatusCodeEquals($response, 200, 'configurationoptions', $requestInfo);

        $optionKeys = [];
        // check that the result is a list of configuration options
        // check that each returned option is accessible via the "get" action
        $content = self::jsonToArray($response->getContent());
        foreach ($content as $key => $item) {
            $itemRequestInfo = sprintf('%s. item index: %s', $requestInfo, $key);
            self::assertArrayHasKey('key', $item, $itemRequestInfo);
            self::assertArrayHasKey('value', $item, $itemRequestInfo);
            self::assertArrayHasKey('type', $item, $itemRequestInfo);
            self::assertArrayHasKey('createdAt', $item, $itemRequestInfo);
            self::assertArrayHasKey('updatedAt', $item, $itemRequestInfo);
            $this->checkGet($item['key']);
            $optionKeys[] = $item['key'];
        }

        return $optionKeys;
    }

    private function checkGet(string $optionKey): void
    {
        $response = $this->get(['entity' => 'configurationoptions', 'id' => $optionKey]);
        $requestInfo = sprintf('get->%s', $optionKey);
        $content = self::jsonToArray($response->getContent());
        self::assertArrayHasKey('key', $content, $requestInfo);
        self::assertEquals($optionKey, $content['key'], $requestInfo);
        self::assertArrayHasKey('value', $content, $requestInfo);
        self::assertArrayHasKey('type', $content, $requestInfo);
        self::assertArrayHasKey('createdAt', $content, $requestInfo);
        self::assertArrayHasKey('updatedAt', $content, $requestInfo);
    }

    /**
     * @depends testGetList
     */
    public function testGetListWithPagination(array $allOptionKeys): void
    {
        if (\count($allOptionKeys) < 6) {
            $this->markTestSkipped('Not enough options for this test.');
        }

        $requestInfo = 'get_list. page size = 0';
        $response = $this->cget(
            ['entity' => 'configurationoptions'],
            ['limit' => 0],
            ['HTTP_X-Include' => 'totalCount']
        );
        self::assertApiResponseStatusCodeEquals($response, 200, 'configurationoptions', $requestInfo);
        $content = self::jsonToArray($response->getContent());
        self::assertCount(0, $content, $requestInfo);
        self::assertEquals(\count($allOptionKeys), $response->headers->get('X-Include-Total-Count'), $requestInfo);

        $requestInfo = 'get_list. 1st page';
        $response = $this->cget(
            ['entity' => 'configurationoptions'],
            ['limit' => 2],
            ['HTTP_X-Include' => 'totalCount']
        );
        self::assertApiResponseStatusCodeEquals($response, 200, 'configurationoptions', $requestInfo);
        $content = self::jsonToArray($response->getContent());
        self::assertCount(2, $content, $requestInfo);
        self::assertEquals($allOptionKeys[0], $content[0]['key'], $requestInfo . '. item index: 0');
        self::assertEquals($allOptionKeys[1], $content[1]['key'], $requestInfo . '. item index: 1');
        self::assertEquals(\count($allOptionKeys), $response->headers->get('X-Include-Total-Count'), $requestInfo);

        $requestInfo = 'get_list. 2nd page';
        $response = $this->cget(
            ['entity' => 'configurationoptions'],
            ['limit' => 2, 'page' => 2],
            ['HTTP_X-Include' => 'totalCount']
        );
        self::assertApiResponseStatusCodeEquals($response, 200, 'configurationoptions', $requestInfo);
        $content = self::jsonToArray($response->getContent());
        self::assertCount(2, $content, $requestInfo);
        self::assertEquals($allOptionKeys[2], $content[0]['key'], $requestInfo . '. item index: 0');
        self::assertEquals($allOptionKeys[3], $content[1]['key'], $requestInfo . '. item index: 1');
        self::assertEquals(\count($allOptionKeys), $response->headers->get('X-Include-Total-Count'), $requestInfo);

        $requestInfo = 'get_list. 3rd page';
        $response = $this->cget(
            ['entity' => 'configurationoptions'],
            ['limit' => 2, 'page' => 3]
        );
        self::assertApiResponseStatusCodeEquals($response, 200, 'configurationoptions', $requestInfo);
        $content = self::jsonToArray($response->getContent());
        self::assertCount(2, $content, $requestInfo);
        self::assertEquals($allOptionKeys[4], $content[0]['key'], $requestInfo . '. item index: 0');
        self::assertEquals($allOptionKeys[5], $content[1]['key'], $requestInfo . '. item index: 1');
        self::assertFalse($response->headers->has('X-Include-Total-Count'), $requestInfo);
    }

    public function testGetListFilterByOptionKey(): void
    {
        $response = $this->cget(
            ['entity' => 'configurationoptions'],
            ['key' => 'oro_navigation.title_delimiter']
        );
        self::assertResponseStatusCodeEquals($response, 200);
        $content = self::jsonToArray($response->getContent());
        self::assertCount(1, $content);
    }

    public function testGetListFilterBySeveralOptionKeys(): void
    {
        $response = $this->cget(
            ['entity' => 'configurationoptions'],
            ['key' => 'oro_navigation.title_delimiter,oro_navigation.title_suffix']
        );
        self::assertResponseStatusCodeEquals($response, 200);
        $content = self::jsonToArray($response->getContent());
        self::assertCount(2, $content);
    }

    public function testTryToGetListWithSorting(): void
    {
        $response = $this->cget(
            ['entity' => 'configurationoptions'],
            ['sort' => 'key'],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The filter is not supported.',
                'source' => 'sort'
            ],
            $response
        );
    }

    public function testTryToGetUnknown(): void
    {
        $response = $this->get(
            ['entity' => 'configurationoptions', 'id' => 'unknown.option'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testGetListWithScope(): void
    {
        $response = $this->cget(['entity' => 'configurationoptions'], ['scope' => 'global']);
        self::assertNotEmpty(self::jsonToArray($response->getContent()));
    }

    public function testGetWithScope(): void
    {
        $response = $this->get(
            ['entity' => 'configurationoptions', 'id' => 'oro_navigation.title_delimiter'],
            ['scope' => 'global']
        );
        self::assertNotEmpty(self::jsonToArray($response->getContent()));
    }
}
