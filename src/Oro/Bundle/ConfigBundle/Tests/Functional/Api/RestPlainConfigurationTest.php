<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestPlainApiTestCase;

class RestPlainConfigurationTest extends RestPlainApiTestCase
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

        // check that the result is a list of configuration section ids
        // check that each returned section is accessible
        $content = $this->jsonToArray($response->getContent());
        foreach ($content as $key => $sectionId) {
            $this->assertTrue(
                is_string($sectionId),
                sprintf(
                    '%s. expected a string, got "%s". item index: %s',
                    $requestInfo,
                    is_object($sectionId) ? get_class($sectionId) : gettype($sectionId),
                    $key
                )
            );
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
        // check that the result is a list of configuration options
        foreach ($content as $key => $item) {
            $this->assertArrayHasKey('key', $item, sprintf('%s. item index: %s', $requestInfo, $key));
        }
    }
}
