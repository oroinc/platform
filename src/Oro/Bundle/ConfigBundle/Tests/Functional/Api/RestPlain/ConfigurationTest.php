<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Api\RestPlain;

use Oro\Bundle\ApiBundle\Tests\Functional\RestPlainApiTestCase;

class ConfigurationTest extends RestPlainApiTestCase
{
    public function testGetConfigurationSections()
    {
        $entityType = 'configuration';

        $response = $this->request(
            'GET',
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType])
        );
        $requestInfo = 'get_list';
        self::assertApiResponseStatusCodeEquals($response, 200, $entityType, $requestInfo);

        // check that the result is a list of configuration section ids
        // check that each returned section is accessible
        $content = self::jsonToArray($response->getContent());
        foreach ($content as $key => $sectionId) {
            self::assertTrue(
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
            $this->getUrl($this->getItemRouteName(), ['entity' => $entityType, 'id' => $sectionId])
        );
        $requestInfo = sprintf('get->%s', $sectionId);
        self::assertApiResponseStatusCodeEquals($response, 200, $entityType, $requestInfo);
        $content = self::jsonToArray($response->getContent());
        // check that the result is a list of configuration options
        foreach ($content as $key => $item) {
            self::assertArrayHasKey('key', $item, sprintf('%s. item index: %s', $requestInfo, $key));
        }
    }
}
