<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ConfigurationControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
    }

    public function testGetList(): array
    {
        $this->client->jsonRequest('GET', $this->getUrl('oro_api_get_configurations'));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertNotEmpty($result);

        return $result;
    }

    /**
     * @depends testGetList
     */
    public function testGet(array $sections)
    {
        foreach ($sections as $sectionPath) {
            $this->client->jsonRequest(
                'GET',
                $this->getUrl('oro_api_get_configuration', ['path' => $sectionPath]),
                [],
                $this->generateWsseAuthHeader()
            );

            $result = $this->getApiJsonResponseContent($this->client->getResponse(), 200, $sectionPath);
            $this->assertNotEmpty($result, sprintf('Section: "%s"', $sectionPath));
        }
    }

    private function getApiJsonResponseContent(Response $response, int $statusCode, string $sectionPath): array
    {
        try {
            $this->assertResponseStatusCodeEquals($response, $statusCode);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            throw new \PHPUnit\Framework\ExpectationFailedException(
                sprintf(
                    'Wrong %s response for section: "%s". Error message: %s',
                    $statusCode,
                    $sectionPath,
                    $e->getMessage()
                ),
                $e->getComparisonFailure()
            );
        }

        return self::jsonToArray($response->getContent());
    }
}
