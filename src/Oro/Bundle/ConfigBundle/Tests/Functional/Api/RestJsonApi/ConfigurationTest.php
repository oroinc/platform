<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class ConfigurationTest extends RestJsonApiTestCase
{
    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'configuration']);

        // check that the result is a list of configuration section identity objects
        // check that each returned section is accessible
        $requestInfo = 'get_list';
        $content = self::jsonToArray($response->getContent());
        self::assertArrayHasKey('data', $content, $requestInfo);
        self::assertArrayNotHasKey('included', $content, $requestInfo);
        foreach ($content['data'] as $key => $item) {
            self::assertArrayHasKey('id', $item, sprintf('%s. item index: %s', $requestInfo, $key));
            self::assertArrayHasKey('type', $item, sprintf('%s. item index: %s', $requestInfo, $key));
            self::assertEquals(
                'configuration',
                $item['type'],
                sprintf('%s. unexpected entity type. item index: %s', $requestInfo, $key)
            );
            self::assertArrayNotHasKey('attributes', $item, sprintf('%s. item index: %s', $requestInfo, $key));
            self::assertArrayHasKey('relationships', $item, sprintf('%s. item index: %s', $requestInfo, $key));
            self::assertArrayHasKey(
                'data',
                $item['relationships']['options'],
                sprintf('%s. item index: %s', $requestInfo, $key)
            );
            $sectionId = $item['id'];
            $this->checkGet($sectionId);
        }
    }

    private function checkGet(string $sectionId): void
    {
        $response = $this->get(['entity' => 'configuration', 'id' => $sectionId]);

        $requestInfo = sprintf('get->%s', $sectionId);
        $content = self::jsonToArray($response->getContent());
        self::assertArrayHasKey('data', $content, $requestInfo);
        self::assertArrayNotHasKey('included', $content, $requestInfo);
        self::assertArrayHasKey('relationships', $content['data'], $requestInfo);
        self::assertArrayHasKey('options', $content['data']['relationships'], $requestInfo);
        self::assertArrayHasKey('data', $content['data']['relationships']['options'], $requestInfo);
    }

    public function testGetListWhenOnlyIdsRequested(): void
    {
        $response = $this->cget(['entity' => 'configuration'], ['fields[configuration]' => 'id']);

        // check that the result is a list of configuration section identity objects
        // check that each returned section is accessible
        $requestInfo = 'get_list';
        $content = self::jsonToArray($response->getContent());
        self::assertArrayHasKey('data', $content, $requestInfo);
        self::assertArrayNotHasKey('included', $content, $requestInfo);
        foreach ($content['data'] as $key => $item) {
            self::assertArrayHasKey('id', $item, sprintf('%s. item index: %s', $requestInfo, $key));
            self::assertArrayHasKey('type', $item, sprintf('%s. item index: %s', $requestInfo, $key));
            self::assertEquals(
                'configuration',
                $item['type'],
                sprintf('%s. unexpected entity type. item index: %s', $requestInfo, $key)
            );
            self::assertArrayNotHasKey('attributes', $item, sprintf('%s. item index: %s', $requestInfo, $key));
            self::assertArrayNotHasKey('relationships', $item, sprintf('%s. item index: %s', $requestInfo, $key));
        }
    }

    public function testGetListWithExpandedOptions(): void
    {
        $response = $this->cget([
            'entity'                => 'configuration',
            'fields[configuration]' => 'options',
            'include'               => 'options'
        ]);

        // check that the result contains full info about configuration section and its options
        // check that each returned section is accessible and contains full info including options
        $requestInfo = 'get_list';
        $content = self::jsonToArray($response->getContent());
        self::assertArrayHasKey('data', $content, $requestInfo);
        self::assertArrayHasKey('included', $content, $requestInfo);
        foreach ($content['data'] as $key => $item) {
            self::assertArrayHasKey('id', $item, sprintf('%s. item index: %s', $requestInfo, $key));
            self::assertArrayHasKey('type', $item, sprintf('%s. item index: %s', $requestInfo, $key));
            self::assertEquals(
                'configuration',
                $item['type'],
                sprintf('%s. unexpected entity type. item index: %s', $requestInfo, $key)
            );
            self::assertArrayHasKey(
                'relationships',
                $item,
                sprintf('%s. item index: %s', $requestInfo, $key)
            );
            self::assertArrayHasKey(
                'options',
                $item['relationships'],
                sprintf('%s. item index: %s', $requestInfo, $key)
            );
            self::assertArrayHasKey(
                'data',
                $item['relationships']['options'],
                sprintf('%s. item index: %s', $requestInfo, $key)
            );
            $sectionId = $item['id'];
            $this->checkGetWithExpandedOptions($sectionId);
        }
        foreach ($content['included'] as $key => $item) {
            $itemRequestInfo = sprintf('%s. item index: %s. included', $requestInfo, $key);
            self::assertArrayHasKey('id', $item, $itemRequestInfo);
            self::assertArrayHasKey('type', $item, $itemRequestInfo);
            self::assertEquals(
                'configurationoptions',
                $item['type'],
                sprintf('%s. unexpected entity type', $itemRequestInfo)
            );
            self::assertArrayHasKey(
                'attributes',
                $item,
                $itemRequestInfo
            );
            self::assertArrayHasKey(
                'value',
                $item['attributes'],
                $itemRequestInfo
            );
        }
    }

    private function checkGetWithExpandedOptions(string $sectionId): void
    {
        $response = $this->get([
            'entity'                => 'configuration',
            'id'                    => $sectionId,
            'fields[configuration]' => 'options',
            'include'               => 'options'
        ]);

        $requestInfo = sprintf('get->%s', $sectionId);
        $content = self::jsonToArray($response->getContent());
        self::assertArrayHasKey('data', $content, $requestInfo);
        self::assertArrayHasKey('included', $content, $requestInfo);
        self::assertArrayHasKey('relationships', $content['data'], $requestInfo);
        self::assertArrayHasKey('options', $content['data']['relationships'], $requestInfo);
        self::assertArrayHasKey('data', $content['data']['relationships']['options'], $requestInfo);
        foreach ($content['included'] as $key => $item) {
            $itemRequestInfo = sprintf('%s. item index: %s. included', $requestInfo, $key);
            self::assertArrayHasKey('id', $item, $itemRequestInfo);
            self::assertArrayHasKey('type', $item, $itemRequestInfo);
            self::assertEquals(
                'configurationoptions',
                $item['type'],
                sprintf('%s. unexpected entity type', $itemRequestInfo)
            );
            self::assertArrayHasKey('attributes', $item, $itemRequestInfo);
            self::assertArrayHasKey('value', $item['attributes'], $itemRequestInfo);
            $attributes = $item['attributes'];
            self::assertArrayHasKey('scope', $attributes, $itemRequestInfo);
            self::assertArrayHasKey('value', $attributes, $itemRequestInfo);
            self::assertArrayHasKey('dataType', $attributes, $itemRequestInfo);
            self::assertArrayHasKey('createdAt', $attributes, $itemRequestInfo);
            self::assertArrayHasKey('updatedAt', $attributes, $itemRequestInfo);
            self::assertEquals('user', $attributes['scope'], $itemRequestInfo);
        }
    }

    public function testTryToGetListWithSorting(): void
    {
        $response = $this->cget(
            ['entity' => 'configuration'],
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
            ['entity' => 'configuration'],
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
            ['entity' => 'configuration', 'id' => 'unknown.section'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testGetListWithScope(): void
    {
        $response = $this->cget(['entity' => 'configuration'], ['scope' => 'global']);
        $content = self::jsonToArray($response->getContent());
        self::assertNotEmpty($content['data']);
    }

    public function testGetWithScope(): void
    {
        $response = $this->get(['entity' => 'configuration', 'id' => 'application'], ['scope' => 'global']);
        $content = self::jsonToArray($response->getContent());
        self::assertNotEmpty($content['data']);
    }
}
