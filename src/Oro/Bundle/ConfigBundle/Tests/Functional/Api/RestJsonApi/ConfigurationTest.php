<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class ConfigurationTest extends RestJsonApiTestCase
{
    public function testGetConfigurationSections()
    {
        $entityType = 'configuration';

        $response = $this->cget(['entity' => $entityType]);

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
                $entityType,
                $item['type'],
                sprintf('%s. unexpected entity type. item index: %s', $requestInfo, $key)
            );
            self::assertArrayNotHasKey('attributes', $item, sprintf('%s. item index: %s', $requestInfo, $key));
            self::assertArrayNotHasKey('relationships', $item, sprintf('%s. item index: %s', $requestInfo, $key));
            $sectionId = $item['id'];
            $this->checkGetConfigurationSection($sectionId);
        }
    }

    private function checkGetConfigurationSection(string $sectionId)
    {
        $entityType = 'configuration';

        $response = $this->get(['entity' => $entityType, 'id' => $sectionId]);

        $requestInfo = sprintf('get->%s', $sectionId);
        $content = self::jsonToArray($response->getContent());
        self::assertArrayHasKey('data', $content, $requestInfo);
        self::assertArrayNotHasKey('included', $content, $requestInfo);
        self::assertArrayHasKey('relationships', $content['data'], $requestInfo);
        self::assertArrayHasKey('options', $content['data']['relationships'], $requestInfo);
        self::assertArrayHasKey('data', $content['data']['relationships']['options'], $requestInfo);
    }

    public function testGetExpandedConfigurationSections()
    {
        $entityType = 'configuration';

        $response = $this->cget([
            'entity'                => $entityType,
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
                $entityType,
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
            $this->checkGetExpandedConfigurationSection($sectionId);
        }
        foreach ($content['included'] as $key => $item) {
            self::assertArrayHasKey('id', $item, sprintf('%s. included. item index: %s', $requestInfo, $key));
            self::assertArrayHasKey('type', $item, sprintf('%s. included. item index: %s', $requestInfo, $key));
            self::assertEquals(
                'configurationoptions',
                $item['type'],
                sprintf('%s. included. unexpected entity type. item index: %s', $requestInfo, $key)
            );
            self::assertArrayHasKey(
                'attributes',
                $item,
                sprintf('%s. included. item index: %s', $requestInfo, $key)
            );
            self::assertArrayHasKey(
                'value',
                $item['attributes'],
                sprintf('%s. included. item index: %s', $requestInfo, $key)
            );
        }
    }

    private function checkGetExpandedConfigurationSection(string $sectionId)
    {
        $entityType = 'configuration';

        $response = $this->get([
            'entity'                => $entityType,
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
            self::assertArrayHasKey('id', $item, sprintf('%s. included. item index: %s', $requestInfo, $key));
            self::assertArrayHasKey('type', $item, sprintf('%s. included. item index: %s', $requestInfo, $key));
            self::assertEquals(
                'configurationoptions',
                $item['type'],
                sprintf('%s. included. unexpected entity type. item index: %s', $requestInfo, $key)
            );
            self::assertArrayHasKey(
                'attributes',
                $item,
                sprintf('%s. included. item index: %s', $requestInfo, $key)
            );
            self::assertArrayHasKey(
                'value',
                $item['attributes'],
                sprintf('%s. included. item index: %s', $requestInfo, $key)
            );
        }
    }

    public function testTryToGetTitle()
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
}
