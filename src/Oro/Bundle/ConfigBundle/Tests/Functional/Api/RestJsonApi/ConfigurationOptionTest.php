<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class ConfigurationOptionTest extends RestJsonApiTestCase
{
    public function testGetList(): array
    {
        $response = $this->cget(['entity' => 'configurationoptions'], ['page[size]' => -1]);
        $requestInfo = 'get_list';
        self::assertApiResponseStatusCodeEquals($response, 200, 'configurationoptions', $requestInfo);

        $optionKeys = [];
        // check that the result is a list of configuration options
        // check that each returned option is accessible via the "get" action
        $content = self::jsonToArray($response->getContent());
        self::assertArrayHasKey('data', $content, $requestInfo);
        foreach ($content['data'] as $key => $item) {
            $itemRequestInfo = sprintf('%s. item index: %s', $requestInfo, $key);
            self::assertArrayHasKey('id', $item, $itemRequestInfo);
            self::assertArrayHasKey('type', $item, $itemRequestInfo);
            self::assertEquals(
                'configurationoptions',
                $item['type'],
                sprintf('%s. unexpected entity type', $itemRequestInfo)
            );
            self::assertArrayHasKey('attributes', $item, $itemRequestInfo);
            self::assertArrayNotHasKey('relationships', $item, $itemRequestInfo);
            $attributes = $item['attributes'];
            self::assertArrayHasKey('scope', $attributes, $itemRequestInfo);
            self::assertEquals('user', $attributes['scope'], $itemRequestInfo);
            self::assertArrayHasKey('value', $attributes, $itemRequestInfo);
            self::assertArrayHasKey('dataType', $attributes, $itemRequestInfo);
            self::assertArrayHasKey('createdAt', $attributes, $itemRequestInfo);
            self::assertArrayHasKey('updatedAt', $attributes, $itemRequestInfo);
            $this->checkGet($item['id']);
            $optionKeys[] = $item['id'];
        }

        return $optionKeys;
    }

    private function checkGet(string $optionKey): void
    {
        $response = $this->get(['entity' => 'configurationoptions', 'id' => $optionKey]);
        $requestInfo = sprintf('get->%s', $optionKey);
        self::assertApiResponseStatusCodeEquals($response, 200, 'configurationoptions', $requestInfo);
        $content = self::jsonToArray($response->getContent());
        self::assertArrayHasKey('data', $content, $requestInfo);
        $data = $content['data'];
        self::assertArrayHasKey('id', $data, $requestInfo);
        self::assertArrayHasKey('type', $data, $requestInfo);
        self::assertEquals($optionKey, $data['id'], $requestInfo);
        self::assertEquals(
            'configurationoptions',
            $data['type'],
            sprintf('%s. unexpected entity type', $requestInfo)
        );
        self::assertArrayHasKey('attributes', $data, $requestInfo);
        self::assertArrayNotHasKey('relationships', $data, $requestInfo);
        $attributes = $data['attributes'];
        self::assertArrayHasKey('scope', $attributes, $requestInfo);
        self::assertArrayHasKey('value', $attributes, $requestInfo);
        self::assertArrayHasKey('dataType', $attributes, $requestInfo);
        self::assertArrayHasKey('createdAt', $attributes, $requestInfo);
        self::assertArrayHasKey('updatedAt', $attributes, $requestInfo);
        self::assertEquals('user', $attributes['scope'], $requestInfo);
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
            ['page[size]' => 0],
            ['HTTP_X-Include' => 'totalCount']
        );
        self::assertApiResponseStatusCodeEquals($response, 200, 'configurationoptions', $requestInfo);
        $content = self::jsonToArray($response->getContent());
        self::assertCount(0, $content['data'], $requestInfo);
        self::assertEquals(\count($allOptionKeys), $response->headers->get('X-Include-Total-Count'), $requestInfo);

        $requestInfo = 'get_list. 1st page';
        $response = $this->cget(
            ['entity' => 'configurationoptions'],
            ['page[size]' => 2],
            ['HTTP_X-Include' => 'totalCount']
        );
        self::assertApiResponseStatusCodeEquals($response, 200, 'configurationoptions', $requestInfo);
        $content = self::jsonToArray($response->getContent());
        self::assertCount(2, $content['data'], $requestInfo);
        self::assertEquals($allOptionKeys[0], $content['data'][0]['id'], $requestInfo . '. item index: 0');
        self::assertEquals($allOptionKeys[1], $content['data'][1]['id'], $requestInfo . '. item index: 1');
        self::assertEquals(\count($allOptionKeys), $response->headers->get('X-Include-Total-Count'), $requestInfo);

        $requestInfo = 'get_list. 2nd page';
        $response = $this->cget(
            ['entity' => 'configurationoptions'],
            ['page[size]' => 2, 'page[number]' => 2],
            ['HTTP_X-Include' => 'totalCount']
        );
        self::assertApiResponseStatusCodeEquals($response, 200, 'configurationoptions', $requestInfo);
        $content = self::jsonToArray($response->getContent());
        self::assertCount(2, $content['data'], $requestInfo);
        self::assertEquals($allOptionKeys[2], $content['data'][0]['id'], $requestInfo . '. item index: 0');
        self::assertEquals($allOptionKeys[3], $content['data'][1]['id'], $requestInfo . '. item index: 1');
        self::assertEquals(\count($allOptionKeys), $response->headers->get('X-Include-Total-Count'), $requestInfo);

        $requestInfo = 'get_list. 3rd page';
        $response = $this->cget(
            ['entity' => 'configurationoptions'],
            ['page[size]' => 2, 'page[number]' => 3]
        );
        self::assertApiResponseStatusCodeEquals($response, 200, 'configurationoptions', $requestInfo);
        $content = self::jsonToArray($response->getContent());
        self::assertCount(2, $content['data'], $requestInfo);
        self::assertEquals($allOptionKeys[4], $content['data'][0]['id'], $requestInfo . '. item index: 0');
        self::assertEquals($allOptionKeys[5], $content['data'][1]['id'], $requestInfo . '. item index: 1');
        self::assertFalse($response->headers->has('X-Include-Total-Count'), $requestInfo);
    }

    public function testGetListFilterByOptionKey(): void
    {
        $response = $this->cget(
            ['entity' => 'configurationoptions'],
            ['filter[id]' => 'oro_navigation.title_delimiter']
        );
        self::assertResponseStatusCodeEquals($response, 200);
        $content = self::jsonToArray($response->getContent());
        self::assertCount(1, $content['data']);
    }

    public function testGetListFilterBySeveralOptionKeys(): void
    {
        $response = $this->cget(
            ['entity' => 'configurationoptions'],
            ['filter[id]' => 'oro_navigation.title_delimiter,oro_navigation.title_suffix']
        );
        self::assertResponseStatusCodeEquals($response, 200);
        $content = self::jsonToArray($response->getContent());
        self::assertCount(2, $content['data']);
    }

    public function testTryToGetListWithSorting(): void
    {
        $response = $this->cget(
            ['entity' => 'configurationoptions'],
            ['sort' => 'id'],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The filter is not supported.',
                'source' => ['parameter' => 'sort']
            ],
            $response
        );
    }

    public function testTryToGetListWithTitle(): void
    {
        $response = $this->cget(
            ['entity' => 'configurationoptions'],
            ['meta' => 'title'],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The filter is not supported.',
                'source' => ['parameter' => 'meta']
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
        $content = self::jsonToArray($response->getContent());
        self::assertNotEmpty($content['data']);
        foreach ($content['data'] as $item) {
            self::assertEquals('global', $item['attributes']['scope'], $item['id']);
        }
    }

    public function testGetWithScope(): void
    {
        $response = $this->get(
            ['entity' => 'configurationoptions', 'id' => 'oro_navigation.title_delimiter'],
            ['scope' => 'global']
        );
        $content = self::jsonToArray($response->getContent());
        self::assertNotEmpty($content['data']);
        self::assertEquals('global', $content['data']['attributes']['scope']);
    }
}
