<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 */
class ConfigurationControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
    }

    /**
     * @return array
     */
    public function testGetList()
    {
        $this->client->request('GET', $this->getUrl('oro_api_get_configurations'));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertNotEmpty($result);

        return $result;
    }

    /**
     * @depends testGetList
     *
     * @param string[] $sections
     */
    public function testGet(array $sections)
    {
        foreach ($sections as $sectionPath) {
            $this->client->request(
                'GET',
                $this->getUrl('oro_api_get_configuration', ['path' => $sectionPath]),
                [],
                [],
                $this->generateWsseAuthHeader()
            );

            $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
            $this->assertNotEmpty($result);
        }
    }
}
