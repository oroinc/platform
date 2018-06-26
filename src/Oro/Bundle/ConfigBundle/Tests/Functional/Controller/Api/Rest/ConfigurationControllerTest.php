<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

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

            $result = $this->getApiJsonResponseContent($this->client->getResponse(), 200, $sectionPath);
            $this->assertNotEmpty($result, sprintf('Section: "%s"', $sectionPath));
        }
    }

    /**
     * @param Response $response
     * @param integer  $statusCode
     * @param string   $sectionPath
     *
     * @return array
     */
    protected function getApiJsonResponseContent(Response $response, $statusCode, $sectionPath)
    {
        try {
            $this->assertResponseStatusCodeEquals($response, $statusCode);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $e = new \PHPUnit\Framework\ExpectationFailedException(
                sprintf(
                    'Wrong %s response for section: "%s". Error message: %s',
                    $statusCode,
                    $sectionPath,
                    $e->getMessage()
                ),
                $e->getComparisonFailure()
            );
            throw $e;
        }

        return self::jsonToArray($response->getContent());
    }
}
