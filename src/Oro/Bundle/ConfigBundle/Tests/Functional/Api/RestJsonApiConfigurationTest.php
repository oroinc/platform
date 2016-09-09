<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class RestJsonApiConfigurationTest extends RestJsonApiTestCase
{
    public function testGetConfigurationSections()
    {
        $entityType = 'configuration';

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityType])
        );
        $requestInfo = 'get_list';
        $this->assertApiResponseStatusCodeEquals($response, 200, $entityType, $requestInfo);

        // check that the result is a list of configuration section identity objects
        // check that each returned section is accessible
        $content = $this->jsonToArray($response->getContent());
        $this->assertArrayHasKey('data', $content, $requestInfo);
        $this->assertArrayNotHasKey('included', $content, $requestInfo);
        foreach ($content['data'] as $key => $item) {
            $this->assertArrayHasKey('id', $item, sprintf('%s. item index: %s', $requestInfo, $key));
            $this->assertArrayHasKey('type', $item, sprintf('%s. item index: %s', $requestInfo, $key));
            $this->assertEquals(
                $entityType,
                $item['type'],
                sprintf('%s. unexpected entity type. item index: %s', $requestInfo, $key)
            );
            $this->assertArrayNotHasKey('relationships', $item, sprintf('%s. item index: %s', $requestInfo, $key));
            $sectionId = $item['id'];
            $this->checkGetConfigurationSection($sectionId);
        }
    }

    /**
     * @param string $sectionId
     */
    protected function checkGetConfigurationSection($sectionId)
    {
        $entityType = 'configuration';

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_get', ['entity' => $entityType, 'id' => $sectionId])
        );
        $requestInfo = sprintf('get->%s', $sectionId);
        $this->assertApiResponseStatusCodeEquals($response, 200, $entityType, $requestInfo);
        $content = $this->jsonToArray($response->getContent());
        $this->assertArrayHasKey('data', $content, $requestInfo);
        $this->assertArrayNotHasKey('included', $content, $requestInfo);
        $this->assertArrayHasKey('relationships', $content['data'], $requestInfo);
        $this->assertArrayHasKey('options', $content['data']['relationships'], $requestInfo);
        $this->assertArrayHasKey('data', $content['data']['relationships']['options'], $requestInfo);
    }

    public function testGetExpandedConfigurationSections()
    {
        $entityType = 'configuration';

        $response = $this->request(
            'GET',
            $this->getUrl(
                'oro_rest_api_cget',
                [
                    'entity'                => $entityType,
                    'fields[configuration]' => 'options',
                    'include'               => 'options'
                ]
            )
        );
        $requestInfo = 'get_list';
        $this->assertApiResponseStatusCodeEquals($response, 200, $entityType, $requestInfo);

        // check that the result contains full info about configuration section and its options
        // check that each returned section is accessible and contains full info including options
        $content = $this->jsonToArray($response->getContent());
        $this->assertArrayHasKey('data', $content, $requestInfo);
        $this->assertArrayHasKey('included', $content, $requestInfo);
        foreach ($content['data'] as $key => $item) {
            $this->assertArrayHasKey('id', $item, sprintf('%s. item index: %s', $requestInfo, $key));
            $this->assertArrayHasKey('type', $item, sprintf('%s. item index: %s', $requestInfo, $key));
            $this->assertEquals(
                $entityType,
                $item['type'],
                sprintf('%s. unexpected entity type. item index: %s', $requestInfo, $key)
            );
            $this->assertArrayHasKey(
                'relationships',
                $item,
                sprintf('%s. item index: %s', $requestInfo, $key)
            );
            $this->assertArrayHasKey(
                'options',
                $item['relationships'],
                sprintf('%s. item index: %s', $requestInfo, $key)
            );
            $this->assertArrayHasKey(
                'data',
                $item['relationships']['options'],
                sprintf('%s. item index: %s', $requestInfo, $key)
            );
            $sectionId = $item['id'];
            $this->checkGetExpandedConfigurationSection($sectionId);
        }
        foreach ($content['included'] as $key => $item) {
            $this->assertArrayHasKey('id', $item, sprintf('%s. included. item index: %s', $requestInfo, $key));
            $this->assertArrayHasKey('type', $item, sprintf('%s. included. item index: %s', $requestInfo, $key));
            $this->assertEquals(
                'configurationoptions',
                $item['type'],
                sprintf('%s. included. unexpected entity type. item index: %s', $requestInfo, $key)
            );
            $this->assertArrayHasKey(
                'attributes',
                $item,
                sprintf('%s. included. item index: %s', $requestInfo, $key)
            );
            $this->assertArrayHasKey(
                'value',
                $item['attributes'],
                sprintf('%s. included. item index: %s', $requestInfo, $key)
            );
        }
    }

    /**
     * @param string $sectionId
     */
    protected function checkGetExpandedConfigurationSection($sectionId)
    {
        $entityType = 'configuration';

        $response = $this->request(
            'GET',
            $this->getUrl(
                'oro_rest_api_get',
                [
                    'entity'                => $entityType,
                    'id'                    => $sectionId,
                    'fields[configuration]' => 'options',
                    'include'               => 'options'
                ]
            )
        );
        $requestInfo = sprintf('get->%s', $sectionId);
        $this->assertApiResponseStatusCodeEquals($response, 200, $entityType, $requestInfo);
        $content = $this->jsonToArray($response->getContent());
        $this->assertArrayHasKey('data', $content, $requestInfo);
        $this->assertArrayHasKey('included', $content, $requestInfo);
        $this->assertArrayHasKey('relationships', $content['data'], $requestInfo);
        $this->assertArrayHasKey('options', $content['data']['relationships'], $requestInfo);
        $this->assertArrayHasKey('data', $content['data']['relationships']['options'], $requestInfo);
        foreach ($content['included'] as $key => $item) {
            $this->assertArrayHasKey('id', $item, sprintf('%s. included. item index: %s', $requestInfo, $key));
            $this->assertArrayHasKey('type', $item, sprintf('%s. included. item index: %s', $requestInfo, $key));
            $this->assertEquals(
                'configurationoptions',
                $item['type'],
                sprintf('%s. included. unexpected entity type. item index: %s', $requestInfo, $key)
            );
            $this->assertArrayHasKey(
                'attributes',
                $item,
                sprintf('%s. included. item index: %s', $requestInfo, $key)
            );
            $this->assertArrayHasKey(
                'value',
                $item['attributes'],
                sprintf('%s. included. item index: %s', $requestInfo, $key)
            );
        }
    }
}
